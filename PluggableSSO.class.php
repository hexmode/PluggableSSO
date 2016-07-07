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
        wfDebug( __METHOD__ );
        if ( !isset( $_SERVER['REMOTE_USER'] ) ) {
            wfDebugLog( __CLASS__, "The webserver should set REMOTE_USER." );
            return false;
        }
        $username = $_SERVER['REMOTE_USER'];
        $domain = null;
        if ( isset( $GLOBALS['wgAuthRemoteuserDomain'] ) ) {
            $domain = $GLOBALS['wgAuthRemoteuserDomain'];

            list( $name, $userDomain ) = explode( '@', $username );
            if ( $userDomain !== $domain ) {
                wfDebugLog( __CLASS__, "Username didn't have the " .
                            "right domain" );
                return false;
            }
            $username = $name;
        }

        if ( \Hooks::run( 'PluggableSSONotAnonymous', [ $username ] ) ) {
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
        return false;
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
