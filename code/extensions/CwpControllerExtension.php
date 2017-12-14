<?php

namespace CWP\Core\Extension;

use SilverStripe\Core\Extension,
    SilverStripe\Security\PermissionProvider,
    SilverStripe\Control\Session,
    SilverStripe\Security\Member,
    SilverStripe\Security\Permission,
    SilverStripe\Security\BasicAuth,
    SilverStripe\Core\Config\Config,
    SilverStripe\Core\Injector\Injector,
    SilverStripe\Control\Director,
    SilverStripe\Subsite\Subsite,
   \Exception;

class CwpControllerExtension extends Extension implements PermissionProvider
{

    /**
     * Enables SSL redirections - disabling not recommended as it will prevent forcing SSL on admin panel.
     */
    public static $ssl_redirection_enabled = true;

    /**
     * Specify a domain to redirect the vulnerable areas to.
     *
     * If left as null, live instance will set this to <instance-id>.cwp.govt.nz via CWP_SECURE_DOMAIN in _config.php.
     * This allows us to automatically protect vulnerable areas on live even if the frontend cert is not installed.
     *
     * Set to false to redirect to https protocol on current domain (e.g. if you have frontend cert).
     *
     * Set to a domain string (e.g. 'example.com') to force that domain.
     */
    public static $ssl_redirection_force_domain = null;

    /**
     * Enables the BasicAuth protection on all test environments. Disable with caution - it will open up
     * all your UAT and test environments to the world.
     */
    public static $test_basicauth_enabled = true;

    /**
     * Enables the BasicAuth protection on all live environments.
     * Useful for securing sites prior to public launch.
     */
    public static $live_basicauth_enabled = false;

    /**
     * This executes the passed callback with subsite filter disabled,
     * then enabled the filter again before returning the callback result
     * (or throwing the exception the callback raised)
     *
     * @param  callback  $callback - The callback to execute
     * @return mixed     The result of the callback
     * @throws Exception Any exception the callback raised
     */
    protected function callWithSubsitesDisabled($callback)
    {
        $rv = null;

        try {
            if (class_exists('Subsite')) {
                Subsite::disable_subsite_filter(true);
            }

            $rv = call_user_func($callback);
        } catch (Exception $e) {
            if (class_exists('Subsite')) {
                Subsite::disable_subsite_filter(false);
            }

            throw $e;
        }

        if (class_exists('Subsite')) {
            Subsite::disable_subsite_filter(false);
        }

        return $rv;
    }

    /**
     * Trigger Basic Auth protection, except when there's a reason to bypass it
     *  - The source IP address is in the comma-seperated string in the constant CWP_IP_BYPASS_BASICAUTH
     *    (so Pingdom, etc, can access the site)
     *  - There is an identifiable member, that member has the ACCESS_UAT_SERVER permission, and they're trying
     *    to access a white-list of URLs (so people following a reset password link can reset their password)
     */
    protected function triggerBasicAuthProtection()
    {
        $allowWithoutAuth = false;

        // Allow whitelisting IPs for bypassing the basic auth.
        if (defined('CWP_IP_BYPASS_BASICAUTH')) {
            $remote = $_SERVER['REMOTE_ADDR'];
            $bypass = explode(',', CWP_IP_BYPASS_BASICAUTH);

            if (in_array($remote, $bypass)) {
                $allowWithoutAuth = true;
            }
        }

        // First, see if we can get a member to act on, either from a changepassword token or the session
        if (isset($_REQUEST['m']) && isset($_REQUEST['t'])) {
            $member = Member::get()->filter('ID', (int) $_REQUEST['m'])->first();

            if (!$member->validateAutoLoginToken($_REQUEST['t'])) {
                $member = null;
            }
        }
        else if (Session::get('AutoLoginHash')) {
            $member = Member::member_from_autologinhash(Session::get('AutoLoginHash'));
        } else {
            $member = Member::currentUser();
        }

        // Then, if they have the right permissions, check the allowed URLs
        $existingMemberCanAccessUAT = $member && $this->callWithSubsitesDisabled(function() use ($member) {
            return Permission::checkMember($member, 'ACCESS_UAT_SERVER');
        });

        if ($existingMemberCanAccessUAT) {
            $allowed = array(
                '/^Security\/changepassword/',
                '/^Security\/ChangePasswordForm/'
            );

            $relativeURL = Director::makeRelative(Director::absoluteURL($_SERVER['REQUEST_URI']));

            foreach ($allowed as $pattern) {
                $allowWithoutAuth = $allowWithoutAuth || preg_match($pattern, $relativeURL);
            }
        }

        // Finally if they weren't allowed to bypass Basic Auth, trigger it
        if (!$allowWithoutAuth) {
            $this->callWithSubsitesDisabled(function() {
                BasicAuth::requireLogin(
                    _t('Cwp.LoginPrompt', "Please log in with your CMS credentials"), 'ACCESS_UAT_SERVER', true
                );
            });
        }
    }

    /**
     * @return void
     */
    public function onBeforeInit()
    {
        // Grab global injectable service to allow testing.
        $director = Injector::inst()->get('SilverStripe\Control\Director');

        if (Config::inst()->get('CWP\Core\Extension\CwpControllerExtension', 'ssl_redirection_enabled')) {
            // redirect some vulnerable areas to the secure domain
            if (!$director::is_https()) {
                $forceDomain = Config::inst()->get('CWP\Core\Extension\CwpControllerExtension\CwpControllerExtension', 'ssl_redirection_force_domain');

                if ($forceDomain) {
                    $director::forceSSL(array('/^Security/', '/^api/'), $forceDomain);
                } else {
                    $director::forceSSL(array('/^Security/', '/^api/'));
                }
            }
        }

        if (Config::inst()->get('CWP\Core\Extension\CwpControllerExtension', 'test_basicauth_enabled')) {
            // Turn on Basic Auth in testing mode
            if ($director::isTest()) {
                $this->triggerBasicAuthProtection();
            }
        }

        if (Config::inst()->get('CWP\Core\Extension\CwpControllerExtension', 'live_basicauth_enabled')) {
            // Turn on Basic Auth in live mode
            if ($director::isLive()) {
                $this->triggerBasicAuthProtection();
            }
        }
    }

    /**
     * @return array
     */
    public function providePermissions()
    {
        return array(
            'ACCESS_UAT_SERVER' => _t(
                    'Cwp.UatServerPermission', 'Allow users to use their accounts to access the UAT server'
            )
        );
    }

}
