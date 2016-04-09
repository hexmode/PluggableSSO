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

	protected $context = null;
	protected $config = null;
	protected $request = null;
	protected $headers = null;

	protected function init() {
		if ( $this->context === null ) {
			$this->context = RequestContext::getMain();
		}
		if ( $this->config === null ) {
			$this->config = $this->context->getConfig();
		}
		if ( $this->request === null ) {
			$this->request = $this->config->get( 'Request' );
		}
		if ( $this->headers === null ) {
			$this->headers = $this->request->getAllHeaders();
		}
	}

	public function getConfig( $name ) {
		$this->init();
		if ( $this->config->has( $name ) ) {
			return $this->config->get( $name );
		}
		return null;
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
		$this->init();
		$headerName = $this->config->get( 'SSOHeader' );
		if ( !isset( $this->headers[ $headerName ] ) ) {
			wfDebugLog( __CLASS__, "The webserver should set $headerName." );
			return false;
		}
		$username = $this->headers[ $headerName ];
		$domain = null;
		$remoteDomain = $this->getConfig( 'AuthRemoteuserDomain' );
		if ( $remoteDomain ) {
			$bits = explode( '@', $username );
			if ( count( $bits ) !== 2 ) {
				throw new MWException( "Couldn't get username and domain "
					. "from $username" );
			}
			$username = $bits[0];
			$userDomain = $bits[1];
			if ( $userDomain !== $remoteDomain ) {
				throw new MWException( "Username didn't have the right domain. "
					. "Got '$userDomain', wanted '$remoteDomain'\n" );
			}
		}

		$identity = \User::idFromName( "$username" );

		$session_variable = wfWikiID() . "_userid";
		if (
			isset( $_SESSION[$session_variable] ) &&
			$identity != $_SESSION[$session_variable]
		) {
			wfDebugLog( __CLASS__, "Username didn't match session" );
			return false;
		}

		\Hooks::run( 'PluggableSSORealName', array( $realname ) );
		\Hooks::run( 'PluggableSSOEmail', array( $email ) );
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
