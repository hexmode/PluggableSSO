<?php

/**
 *  Copyright (c) 2015 Mark A. Hershberger
 *
 *  This file is part of the PluggableSSO MediaWiki extension
 *
 *  PlugggableSSO is free software: you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  PluggableSSO is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 *  General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with PluggableSSO.  If not, see
 *  <http://www.gnu.org/licenses/>.
 */

namespace PluggableSSO;

use Hooks;
use MWException;
use PluggableAuth;
use MediaWiki\Session\SessionManager;
use PluggableAuthLogin;
use RequestContext;
use User;

abstract class PluggableSSO extends PluggableAuth {

	protected function getConfig() {
		return RequestContext::getMain()->getConfig();
	}

	abstract public function discoverRealname();

	abstract public function discoverEmail();

	/**
	 * @since 1.0
	 *
	 * @param int &$identity ID of user, leave null if new user
	 * @param string &$username username
	 * @param string &$realname real name of user
	 * @param string &$email email address of user
	 * @param string &$errorMessage error message to return
	 * @return boolean false if the username does not match what is in
	 *     the session
	 *
	 * @SuppressWarnings("CamelCaseVariableName")
	 * @SuppressWarnings("SuperGlobals")
	 */
	public function authenticate(
		&$identity, &$username, &$realname, &$email, &$errorMessage
	) {
		if ( $identity === null && $username ) {
			$identity = User::idFromName( $username );
		}
		if ( !$username ) {
			$session = SessionManager::getGlobalSession();
			$user = $session->getUser();
			if ( $user instanceof User ) {
				$username = $user->getName();
				$identity = $user->getID();
			}
		}
		if ( !$username ) {
			$errorMessage = wfMessage( "pluggablesso-no-session" );
			wfDebugLog( __METHOD__, $errorMessage );
			return false;
		}

		$ssoRealName = $this->discoverRealname();
		if ( $ssoRealName && $realname !== $ssoRealName ) {
			wfDebugLog( __METHOD__, "Updating real name from '$realname' ".
						"to '$ssoRealName'\n" );
			$realname = $ssoRealName;
		}
		$ssoEmail = $this->discoverEmail();
		if ( $ssoEmail && $email !== $ssoEmail ) {
			wfDebugLog( __METHOD__, "Updating email from '$email' " .
						"to '$ssoEmail'\n" );
			$email = $ssoEmail;
		}

		$_SESSION[$session_variable] = $identity;
		return true;
	}

	/**
	 * @param User &$user user that is logging out
	 * @return boolean
	 *
	 * @SuppressWarnings("UnusedFormalParameter")
	 */
	public function deauthenticate( User &$user ) {
		return false;
	}

	/**
	 * @param int $identity user id
	 * @return boolean
	 *
	 * @SuppressWarnings("UnusedFormalParameter")
	 */
	public function saveExtraAttributes( $identity ) {
		return false;
	}
}
