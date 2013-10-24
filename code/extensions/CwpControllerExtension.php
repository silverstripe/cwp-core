<?php
class CwpControllerExtension extends Extension implements PermissionProvider {

	/**
	 * This executes the passed callback with subsite filter disabled,
	 * then enabled the filter again before returning the callback result
	 * (or throwing the exception the callback raised)
	 *
	 * @param callback $callback - The callback to execute
	 * @return mixed - The result of the callback
	 * @throws Exception - Any exception the callback raised
	 */
	protected function callWithSubsitesDisabled($callback) {
		$rv = null;

		try {
			if (class_exists('Subsite')) Subsite::disable_subsite_filter(true);
			$rv = call_user_func($callback);
		}
		catch (Exception $e) {
			if (class_exists('Subsite')) Subsite::disable_subsite_filter(false);
			throw $e;
		}

		if (class_exists('Subsite')) Subsite::disable_subsite_filter(false);
		return $rv;
	}

	/**
	 * Trigger Basic Auth protection, except when there's a reason to bypass it
	 *  - The source IP address is in the comma-seperated string in the constant CWP_IP_BYPASS_BASICAUTH
	 *    (so Pingdom, etc, can access the site)
	 *  - There is an identifiable member, that member has the ACCESS_UAT_SERVER permission, and they're trying
	 *    to access a white-list of URLs (so people following a reset password link can reset their password)
	 */
	protected function triggerBasicAuthProtection() {
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
			$member = Member::get()->filter('ID', (int)$_REQUEST['m'])->First();
			if (!$member->validateAutoLoginToken($_REQUEST['t'])) $member = null;
		}
		else if (Session::get('AutoLoginHash')) {
			$member = Member::member_from_autologinhash(Session::get('AutoLoginHash'));
		}
		else {
			$member = Member::currentUser();
		}

		// Then, if they have the right permissions, check the allowed URLs
		$existingMemberCanAccessUAT = $member && $this->callWithSubsitesDisabled(function() use ($member){
			return Permission::checkMember($member, 'ACCESS_UAT_SERVER');
		});

		if ($existingMemberCanAccessUAT) {
			$allowed = array(
				'/^Security\/changepassword/',
				'/^Security\/ChangePasswordForm/'
			);

			$relativeURL = Director::makeRelative(Director::absoluteURL($_SERVER['REQUEST_URI']));

			foreach($allowed as $pattern) {
				$allowWithoutAuth = $allowWithoutAuth || preg_match($pattern, $relativeURL);
			}
		}

		// Finally if they weren't allowed to bypass Basic Auth, trigger it
		if (!$allowWithoutAuth) {
			$this->callWithSubsitesDisabled(function(){
				BasicAuth::requireLogin(
					_t('Cwp.LoginPrompt', "Please log in with your CMS credentials"), 
					'ACCESS_UAT_SERVER', 
					true
				);
			});
		}
	}

	public function onBeforeInit() {
		// redirect some requests to the secure domain
		if(defined('CWP_SECURE_DOMAIN') && !Director::is_https()) {
			Director::forceSSL(array('/^Security/', '/^api/'), CWP_SECURE_DOMAIN);
			// Note 1: the platform always redirects "/admin" to CWP_SECURE_DOMAIN regardless of what you set here
			// Note 2: if you have your own certificate installed, you can use your own domain, just omit the second parameter:
			//   Director::forceSSL(array('/^Security/', '/^api/'));
			//
			// See Director::forceSSL for more information.
		}

		// if there's a proxy setting in the environment, configure RestfulService to use it
		if(defined('SS_OUTBOUND_PROXY')) {
			Config::inst()->update('RestfulService', 'default_curl_options', array(
				CURLOPT_PROXY => SS_OUTBOUND_PROXY,
				CURLOPT_PROXYPORT => SS_OUTBOUND_PROXY_PORT
			));
		}

		// Turn on Basic Auth in testing mode
		if(Director::isTest()) $this->triggerBasicAuthProtection();
	}

	function providePermissions() {
		return array(
			'ACCESS_UAT_SERVER' => _t(
				'Cwp.UatServerPermission',
				'Allow users to use their accounts to access the UAT server'
			)
		);
	}

}
