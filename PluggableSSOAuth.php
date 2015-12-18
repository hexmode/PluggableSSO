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

class Auth extends \AuthPlugin {
	/**
	 * Pretend all users exist.  This is checked by
	 * authenticateUserData to determine if a user exists in our 'db'.
	 * By returning true we tell it that it can create a local wiki
	 * user automatically.
	 * FIXME: Should be configurable
	 *
	 * @param $username String: username.
	 * @return bool
	 *
	 * @SuppressWarnings("UnusedFormalParameter")
	 */
	public function userExists( $username ) {
		return true;
	}

	/**
	 * Check whether the given name matches REMOTE_USER.
	 * The name will be normalized to MediaWiki's requirements, so
	 * lower it and the REMOTE_USER before checking.
	 *
	 * @param $username String: username.
	 * @param $password String: user password.
	 * @return bool
	 */
	public function authenticate( $username, $password ) {
		$usertest = $this->getRemoteUsername();

		return ( strtolower( $username ) == strtolower( $usertest ) );
	}

	/**
	 * Check to see if the specific domain is a valid domain.
	 *
	 * @param string $domain Authentication domain.
	 * @return bool
	 */
	public function validDomain( $domain ) {
		if (
			isset( $GLOBALS['wgAuthRemoteuserDomain'] ) &&
			$domain !== $GLOBALS['wgAuthRemoteuserDomain']
		) {
			return false;
		}
		return true;
	}

	/**
	 * When a user logs in, optionally fill in preferences and such.
	 * For instance, you might pull the email address or real name from the
	 * external user database.
	 *
	 * The User object is passed by reference so it can be modified; don't
	 * forget the & on your function declaration.
	 *
	 * @param User $user
	 * @return bool
	 */
	public function updateUser( &$user ) {
		Hooks::run( "PluggableSSOInitOrUpdateUser", [ &$user, false ] );
	}

	/**
	 * Of course not here
	 *
	 * @return bool
	 */
	public function allowPasswordChange() {
		return false;
	}

	/**
	 * MediaWiki should never see passwords, but if it does, don't store them.
	 *
	 * @return bool
	 */
	public function allowSetLocalPassword() {
		return false;
	}

	/**
	 * This should not be called because we do not allow password
	 * change.  Always fail by returning false.
	 *
	 * @param $user User object.
	 * @param $password String: password.
	 * @return bool
	 */
	public function setPassword( $user, $password ) {
		return false;
	}

	/**
	 * Return true because the wiki should create a new local account
	 * automatically when asked to login a user who doesn't exist
	 * locally but does in the external auth database.
	 *
	 * @return bool
	 */
	public function autoCreate() {
		return true;
	}

	/**
	 * Should never be called, but return false anyway.
	 */
	public function addUser( $user, $password, $email = '', $realname = '' ) {
		return false;
	}

	/**
	 * Return true to prevent logins that don't authenticate here from
	 * being checked against the local database's password fields.
	 *
	 * @return bool
	 */
	public function strict() {
		return true;
	}

	/**
	 * When creating a user account, optionally fill in
	 * preferences and such.
	 *
	 * @param $user User object.
	 * @param $autocreate bool	// true if this is the autocration
	 */
	public function initUser( &$user, $autocreate = false ) {
		Hooks::run( "PluggableSSOInitOrUpdateUser", [ &$user, $autocreate ] );
	}

	/**
	 * Normalize user names to the MediaWiki standard to prevent
	 * duplicate accounts.
	 *
	 * @param $username String: username.
	 * @return string
	 */
	public function getCanonicalName( $username ) {
		// lowercase the username
		$username = strtolower( $username );
		// uppercase first letter to make MediaWiki happy
		return ucfirst( $username );
	}
}
