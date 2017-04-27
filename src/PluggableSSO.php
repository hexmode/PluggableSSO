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
use User;
use PluggableAuth;
use RequestContext;

class PluggableSSO extends PluggableAuth {

	/**
	 * Utility method to determine the username from the
	 * headers. Users can also hook into PluggableSSOSetUserName if
	 * they need to override or augment the username method.
	 * @return string|boolean false if username couldn't be
	 *     determined, string otherwise
	 */
	public function getUsername() {
		$conf = RequestContext::getMain()->getConfig();
		$headerName = $conf->get( 'SSOHeader' );
		$remoteDomain = $conf->get( 'AuthRemoteuserDomain' );
		$remoteDomains = array_flip
					   ( array_merge( [ $remoteDomain ],
									  $conf->get( 'AuthRemoteuserDomains' )
					   ) );
		$username = $conf->get( 'Request' )->getHeader( $headerName );
		Hooks::run( 'PluggableSSOSetUserName', [ &$username ] );

		if ( !$username ) {
			wfDebugLog( __CLASS__, "The webserver should set $headerName." );
			return false;
		}

		if ( $remoteDomains ) {
			$bits = explode( '@', $username );
			if ( count( $bits ) !== 2 ) {
				throw new MWException( "Couldn't get username and domain "
					. "from $username" );
			}
			$username = $bits[0];
			$userDomain = $bits[1];
			if ( isset( $userDomain )
				 && !isset( $remoteDomains[$userDomain] ) ) {
				throw new MWException( "Username didn't have the right domain. "
									   . "Got '$userDomain', wanted one of '"
									   . implode( ", ", $remoteDomains )
									   . "'." );
			}
			if ( isset( $remoteDomains[$userDomain] )
				 && $userDomain !== $remoteDomain ) {
				$username = "$username@$userDomain";
			}
		}
		return $username;
	}

	/**
	 * @since 1.0
	 *
	 * @param int &$identity ID of user, leave null if new user
	 * @param string &$username username
	 * @param string &$realname real name of user
	 * @param string &$email email address of user
	 * @return boolean false if the username matches what is in the
	 *     session
	 *
	 * @SuppressWarnings("CamelCaseVariableName")
	 * @SuppressWarnings("SuperGlobals")
	 */
	public function authenticate(
		&$identity, &$username, &$realname, &$email
	) {
		$username = $this->getUsername();

		$identity = User::idFromName( "$username" );

		$session_variable = wfWikiID() . "_userid";
		if (
			isset( $_SESSION[$session_variable] ) &&
			$identity != $_SESSION[$session_variable]
		) {
			wfDebugLog( __CLASS__, "Username didn't match session" );
			return false;
		}

		$realname = $this->discoverRealname();
		$email = $this->getEmail();

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
