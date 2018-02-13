<?php

namespace CWP\Core\Control;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\BasicAuthMiddleware;
use SilverStripe\Security\PermissionProvider;

class CwpBasicAuthMiddleware extends BasicAuthMiddleware implements PermissionProvider
{
    /**
     * Whitelisted IP addresses will not be given a basic authentication prompt when other basic authentication
     * rules via {@link BasicAuthMiddleware} are enabled.
     *
     * Please note that this will not have any effect if using BasicAuth.entire_site_protected, which will
     * always enabled basic authentication for the entire site.
     *
     * @var array
     */
    protected $whitelistedIps = [];

    /**
     * @return array
     */
    public function getWhitelistedIps()
    {
        return $this->whitelistedIps;
    }

    /**
     * @param string|string[] $whitelistedIps An array of IP addresses, or a comma delimited string
     * @return $this
     */
    public function setWhitelistedIps($whitelistedIps)
    {
        if (is_string($whitelistedIps)) {
            $whitelistedIps = explode(',', $whitelistedIps);
        }
        $this->whitelistedIps = $whitelistedIps;
        return $this;
    }

    /**
     * Check for any whitelisted IP addresses. If one matches the current user's IP then return false early,
     * otherwise allow the default {@link BasicAuthMiddleware} to continue its logic.
     *
     * {@inheritDoc}
     */
    protected function checkMatchingURL(HTTPRequest $request)
    {
        if ($this->ipMatchesWhitelist()) {
            return false;
        }
        return parent::checkMatchingURL($request);
    }

    /**
     * Check whether the current user's IP address is in the IP whitelist
     *
     * @return bool
     */
    protected function ipMatchesWhitelist()
    {
        $whitelist = $this->getWhitelistedIps();
        // Continue if no whitelist is defined
        if (empty($whitelist)) {
            return false;
        }

        $userIp = $_SERVER['REMOTE_ADDR'];
        if (in_array($userIp, $whitelist)) {
            return true;
        }

        return false;
    }

    /**
     * Provide a permission code for users to be able to access the site in test mode (UAT sites). This will
     * apply to any route other than those required to change your password.
     *
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
