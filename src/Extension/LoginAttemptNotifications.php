<?php

namespace CWP\Core\Extension;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\LoginAttempt;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;

/**
 * Requires Security::login_recording config to be set to true.
 *
 * @property LeftAndMain $owner
 */
class LoginAttemptNotifications extends Extension
{

    /**
     *
     * @return mixed null
     */
    public function init()
    {

        // Exclude default admin.
        $member = Security::getCurrentUser();
        if (!$member || !$member->ID) {
            return;
        }

        $message = null;
        $session = $this->owner->getRequest()->getSession();

        Requirements::javascript('cwp/cwp-core:javascript/LoginAttemptNotifications.js');
        $sessionLastVisited = $session->get('LoginAttemptNotifications.SessionLastVisited');
        if ($sessionLastVisited) {
            // Session already in progress. Show all attempts since the session was last visited.

            $meantimeLoginAttempts = LoginAttempt::get()->filter([
                'MemberID' => $member->ID,
                'Created:GreaterThan' => $sessionLastVisited
            ]);

            $attempts = $meantimeLoginAttempts->count();
            if ($attempts) {
                $lastVisitedObj = DBDatetime::create();
                $lastVisitedObj->setValue($sessionLastVisited);
                $elapsed = $lastVisitedObj->TimeDiff();
                $failures = $meantimeLoginAttempts->filter(['Status' => 'Failure'])->count();
                $IPs = array_unique($meantimeLoginAttempts->column('IP') ?? []);

                if ($attempts == 1) {
                    $statusString = $failures ? "a failed" : "a successful";
                    $message = "In the last $elapsed $statusString login attempt to your account was "
                        . "registered. The attempt was made from {$IPs[0]}. ";
                } else {
                    if ($failures == $attempts) {
                        $statusString = $failures ? "failed" : "successful";
                        $message = "In the last $elapsed $attempts $statusString login "
                            . "attempts to your account were registered. ";
                    } else {
                        $message = "In the last $elapsed $attempts login attempts to your "
                            . "account were registered, of which $failures failed. ";
                    }

                    $message .= "The attempts were from " . implode(', ', $IPs) . '. ';

                    $message .= "If you suspect somebody else might be trying to access "
                    . "your account, please contact support.";
                }
            }
        } else {
            // New session - show last login attempt.
            $lastLoginAttempt = LoginAttempt::get()->filter([
                        'MemberID' => $member->ID
            ])->sort('Created DESC')->First();

            if ($lastLoginAttempt) {
                $date = $lastLoginAttempt->Created;
                $message = "Last login attempt to your account was on $lastLoginAttempt->Created "
                    . "from $lastLoginAttempt->IP";
                $message .= $lastLoginAttempt->Status == 'Failure' ? " and was successful." : "and has failed.";
            }
        }

        $session->set(
            'LoginAttemptNotifications.SessionLastVisited',
            DBDatetime::now()->Format(DBDatetime::ISO_DATETIME)
        );

        $this->owner->getResponse()->addHeader('X-LoginAttemptNotifications', $message);
    }
}
