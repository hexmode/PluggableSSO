<?php
/**
 * A session provider for PluggableSSO
 *
 * Copyright (C) 2017  Mark A. Hershberger
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace PluggableSSO;

use MediaWiki\Session\CookieSessionProvider;
use MediaWiki\Session\SessionInfo;
use MediaWiki\Session\UserInfo;
use MWException;
use RequestContext;
use WebRequest;

class SessionProvider extends CookieSessionProvider {
    protected $config;

	/**
	 * Provide session info for a request.  Only comes into play if
	 * the parent doesn't provide a user.
     *
     * Returns a SessionInfo object identifying the session.
     *
     * Note that there is no $wgUser, $wgLang, $wgOut, $wgParser, $wgTitle or
     * RequestContext.
     *
	 * @param WebRequest $request
	 * @return SessionInfo|null
	 */
    public function provideSessionInfo( WebRequest $request ) {
        $session = parent::provideSessionInfo( $request );
        if ( $session === null ) {
            # FIXME: cut 'n paste from PluggableSSO::getUsername
            $conf = $this->getConfig();
            $headerName = $conf->get( 'SSOHeader' );
            $username = $request->getHeader( 'SM_EMAIL' );
            if ( !$username ) {
                wfDebugLog( __CLASS__, "The webserver should set $headerName." );
                return false;
            }
            $username = $this->checkMultiDomain( $username );

            $info = [
                'userInfo' => UserInfo::newFromName( $username, true ),
                'provider' => $this
            ];

            $session = new SessionInfo( $this->priority, $info );
        }
        return $session;
    }

    // FIXME: Cut-n-paste from PluggableSSO.php
	protected function checkMultiDomain( $username ) {
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


	protected function getConfig() {
        if ( !$this->config ) {
            $this->config = RequestContext::getMain()->getConfig();
        }

		return $this->config;
	}

}