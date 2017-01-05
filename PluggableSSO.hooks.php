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

class Hooks {

	/**
	 * Hook to set up the extension.
	 *
	 * @SuppressWarnings("CamelCaseVariableName")
	 * @SuppressWarnings("SuperGlobals")
	 */
	public static function onAuthPluginSetup( $wgAuth ) {
		\Hooks::run( 'PluggableSSOAuth', array( &$wgAuth ) );
	}

	public static function initExtension() {
		$extensionData = \ExtensionRegistry::getInstance()->getAllThings();
		$prefix = '';
		$configAutoLogin = 'PluggableAuth_AutoLogin';
		if ( isset( $extensionData['PluggableAuth'] ) ) {
			$pluggablAuthVersion = $extensionData['PluggableAuth']['version'];
			if ( version_compare( $pluggablAuthVersion, '2.0', '>=' ) ) {
				// PluggableAuth 2.0 prefixed config Variables with 'wg' 
				// and renamed '...AutoLogin' to '...EnableAutoLogin'
				$prefix = 'wg';
				$configAutoLogin = 'PluggableAuth_EnableAutoLogin';
			}
		}
			
		if ( array_key_exists( $prefix . 'PluggableAuth_Class', $GLOBALS ) ) {
			return;
		}
		wfDebugLog( __METHOD__, "initializing" );
		$GLOBALS[$prefix . 'PluggableAuth_Class'] = 'PluggableSSO';
		$GLOBALS[$prefix . 'PluggableAuth_Timeout'] = 0;
		$GLOBALS[$prefix . $configAutoLogin] = true;
	}
}
