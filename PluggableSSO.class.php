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


class PluggableSSO extends PluggableAuth {

	static public function getUsername() {
		$conf = RequestContext::getMain()->getConfig();
		$headerName = $conf->get( 'SSOHeader' );
		$remoteDomain = $conf->get( 'AuthRemoteuserDomain' );
		$remoteDomains = array_flip( array_merge( [ $remoteDomain ],
												  $conf->get( 'AuthRemoteuserDomains' ) ) );
		$username = $conf->get( 'Request' )->getHeader( $headerName );

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
			if ( isset( $userDomain ) && !isset( $remoteDomains[$userDomain] ) ) {
				throw new MWException( "Username didn't have the right domain. "
									   . "Got '$userDomain', wanted one of '"
									   . implode( ", ", $remoteDomains )
									   . "'." );
			}
			if ( isset( $remoteDomains[$userDomain] ) && $userDomain !== $remoteDomain ) {
				$username = "$username@$userDomain";
			}
		}
		return $username;
	}

	/**
	 * get email address and real name from HTTP headers if 
	 * $wgSSOEmailHeader and/or $wgSSORealNameHeader are configured 
	 */
	private function getEmailAndRealName() {
		$email = $realname = "";
		$conf = RequestContext::getMain()->getConfig();

		if ( $conf->has( 'SSOEmailHeader' ) ) {
			$emailHeaderName = $conf->get( 'SSOEmailHeader' );
			$email = $conf->get( 'Request' )->getHeader( $emailHeaderName );
		}

		if ( $conf->has( 'SSORealNameHeader' ) ) {
			$realnameHeaderName = $conf->get( 'SSORealNameHeader' );
			$realname = $conf->get( 'Request' )->getHeader( $realnameHeaderName );
		}

		return array($email, $realname);
	}

	/**
	 * @since 1.0
	 *
	 * @param &$id
	 * @param &$username
	 * @param &$realname
	 * @param &$email
	 *
	 * @SuppressWarnings("CamelCaseVariableName")
	 * @SuppressWarnings("SuperGlobals")
	 */
	public function authenticate(
		&$identity, &$username, &$realname, &$email
	) {
		$username = self::getUsername();

		\Hooks::run( 'PluggableSSOSetUserName', [ &$username ] );
		$identity = \User::idFromName( "$username" );

		$session_variable = wfWikiID() . "_userid";
		if (
			isset( $_SESSION[$session_variable] ) &&
			$identity != $_SESSION[$session_variable]
		) {
			wfDebugLog( __CLASS__, "Username didn't match session" );
			return false;
		}

		list ($email, $realname) = $this->getEmailAndRealName();
		\Hooks::run( 'PluggableSSORealName', array( &$realname ) );
		\Hooks::run( 'PluggableSSOEmail', array( &$email ) );
		$_SESSION[$session_variable] = $identity;
		return true;
	}

	/**
	 * @param User &$user
	 *
	 * @SuppressWarnings("UnusedFormalParameter")
	 */
	public function deauthenticate( User &$user ) {
		wfDebugLog( __CLASS__, "Don't know what to do with this." .
			__METHOD__ );
		return false;
	}

	/**
	 *
	 * @SuppressWarnings("UnusedFormalParameter")
	 */
	public function saveExtraAttributes( $identity ) {
		wfDebugLog( __CLASS__, "Don't know what to do with this: " .
			__METHOD__ );
		return false;
	}
}
