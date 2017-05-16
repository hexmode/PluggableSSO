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
use PluggableAuthLogin;
use RequestContext;
use User;

abstract class PluggableSSO extends PluggableAuth {

	/**
	 * Utility method to determine the username from the
	 * headers. Users can also hook into PluggableSSOSetUserName if
	 * they need to override or augment the username method.
	 * @return string|boolean false if username couldn't be
	 *     determined, string otherwise
	 */
	public function getUsername() {
		$conf = $this->getConfig();
		$headerName = $conf->get( 'SSOHeader' );
		$username = $conf->get( 'Request' )->getHeader( $headerName );
		if ( !$username ) {
			wfDebugLog( __CLASS__, "The webserver should set $headerName." );
			return false;
		}
		$username = $this->checkMultiDomain( $username );
		return $username;
	}

	protected function getConfig() {
		return RequestContext::getMain()->getConfig();
	}

	public function checkMultiDomain( $username ) {
		$conf = $this->getConfig();
		$remoteDomain = $conf->get( 'AuthRemoteuserDomain' );
		$remoteDomains = array_flip
					   ( array_merge( [ $remoteDomain ],
									  $conf->get( 'AuthRemoteuserDomains' )
					   ) );
		$bits = explode( '@', $username );
		if ( count( $bits ) !== 2 ) {
			throw new MWException( "Couldn't get username and domain "
								   . "from $username" );
		}
		$username = $bits[0];
		$userDomain = $bits[1];

		if ( $userDomain !== $remoteDomain ) {
			if ( isset( $userDomain )
				 && !isset( $remoteDomains[$userDomain] ) ) {
				throw new MWException( "Username didn't have the right domain. "
									   . "Got '$userDomain', wanted one of \n* "
									   . implode( "\n* ", array_keys( $remoteDomains ) )
									   . "\n" );
			}
			$username = "$username@$userDomain";
		}
		return $username;
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
	 * @return boolean false if the username does not match what is in
	 *     the session
	 *
	 * @SuppressWarnings("CamelCaseVariableName")
	 * @SuppressWarnings("SuperGlobals")
	 */
	public function authenticate(
		&$identity, &$username, &$realname, &$email, &$errorMessage
	) {
		$username = $this->getUsername();
		$identity = User::idFromName( $username );

		$session_variable = PluggableAuthLogin::USERNAME_SESSION_KEY;
		if (
			isset( $_SESSION[$session_variable] ) &&
			$identity != $_SESSION[$session_variable]
		) {
			$errorMessage = "Username didn't match session";
			wfDebugLog( __CLASS__, $errorMessage );
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
