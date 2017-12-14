<?php

namespace CWP\Core\Extension;

use SilverStripe\Core\Extension,
    SilverStripe\Control\Session,
    SilverStripe\Security\Member;

/**
 * Extends {@see Security} with CWP specific extensions
 *
 * @property Security $owner
 */
class CwpSecurity extends Extension
{

    /**
     * @return HTTPResponse
     */
    public function onBeforeSecurityLogin()
    {
        // Implemented in core in 3.2 https://github.com/silverstripe/silverstripe-framework/pull/4051
        // If arriving on the login page already logged in, with no security error, and a ReturnURL then redirect
        // back. The login message check is neccesary to prevent infinite loops where BackURL links to
        // an action that triggers Security::permissionFailure.
        // This step is necessary in cases such as automatic redirection where a user is authenticated
        // upon landing on an SSL secured site and is automatically logged in, or some other case
        // where the user has permissions to continue but is not given the option.
        if ($this->owner->getRequest()->requestVar('BackURL') && !Session::get('Security.Message.message') && ($member = Member::currentUser()) && $member->exists()
        ) {
            return $this->owner->redirectBack();
        }
    }

}
