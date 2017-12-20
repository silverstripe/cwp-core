<?php

namespace CWP\Core\Extension;

use Exception;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\BasicAuth;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Security;
use SilverStripe\Subsite\Model\Subsite;

class CwpControllerExtension extends Extension implements PermissionProvider
{

    /**
     * Enables SSL redirections - disabling not recommended as it will prevent forcing SSL on admin panel.
     *
     * @config
     * @var bool
     */
    private static $ssl_redirection_enabled = true;

    /**
     * Specify a domain to redirect the vulnerable areas to.
     *
     * If left as null, live instance will set this to <instance-id>.cwp.govt.nz via CWP_SECURE_DOMAIN in _config.php.
     * This allows us to automatically protect vulnerable areas on live even if the frontend cert is not installed.
     *
     * Set to false to redirect to https protocol on current domain (e.g. if you have frontend cert).
     *
     * Set to a domain string (e.g. 'example.com') to force that domain.
     *
     * @config
     * @var string
     */
    private static $ssl_redirection_force_domain = null;

    /**
     * Enables the BasicAuth protection on all test environments. Disable with caution - it will open up
     * all your UAT and test environments to the world.
     *
     * @config
     * @var bool
     */
    private static $test_basicauth_enabled = true;

    /**
     * Enables the BasicAuth protection on all live environments.
     * Useful for securing sites prior to public launch.
     *
     * @config
     * @var bool
     */
    private static $live_basicauth_enabled = false;

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
            if (class_exists(Subsite::class)) {
                Subsite::disable_subsite_filter(true);
            }

            $rv = call_user_func($callback);
        } catch (Exception $e) {
            if (class_exists(Subsite::class)) {
                Subsite::disable_subsite_filter(false);
            }

            throw $e;
        }

        if (class_exists(Subsite::class)) {
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
        if (Environment::getEnv('CWP_IP_BYPASS_BASICAUTH')) {
            $remote = $_SERVER['REMOTE_ADDR'];
            $bypass = explode(',', Environment::getEnv('CWP_IP_BYPASS_BASICAUTH'));

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
        } elseif ($this->owner->getRequest()->getSession()->get('AutoLoginHash')) {
            $member = Member::member_from_autologinhash(
                $this->owner->getRequest()->getSession()->get('AutoLoginHash')
            );
        } else {
            $member = Security::getCurrentUser();
        }

        // Then, if they have the right permissions, check the allowed URLs
        $existingMemberCanAccessUAT = $member && $this->callWithSubsitesDisabled(function () use ($member) {
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
            $this->callWithSubsitesDisabled(function () {
                BasicAuth::requireLogin(
                    $this->owner->getRequest(),
                    _t(__CLASS__ . '.LoginPrompt', "Please log in with your CMS credentials"),
                    'ACCESS_UAT_SERVER',
                    true
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
        $director = Injector::inst()->get(Director::class);

        if (Config::inst()->get(__CLASS__, 'ssl_redirection_enabled')) {
            // redirect some vulnerable areas to the secure domain
            if (!$director::is_https()) {
                $forceDomain = Config::inst()->get(__CLASS__, 'ssl_redirection_force_domain');

                if ($forceDomain) {
                    $director::forceSSL(['/^Security/', '/^api/'], $forceDomain);
                } else {
                    $director::forceSSL(['/^Security/', '/^api/']);
                }
            }
        }

        if (Config::inst()->get(__CLASS__, 'test_basicauth_enabled')) {
            // Turn on Basic Auth in testing mode
            if ($director::isTest()) {
                $this->triggerBasicAuthProtection();
            }
        }

        if (Config::inst()->get(__CLASS__, 'live_basicauth_enabled')) {
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
        return [
            'ACCESS_UAT_SERVER' => _t(
                __CLASS__ . '.UatServerPermission',
                'Allow users to use their accounts to access the UAT server'
            )
        ];
    }
}
