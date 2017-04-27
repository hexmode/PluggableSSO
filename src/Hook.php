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

class Hook {

	/**
	 * Extension initialization
	 * @return null
	 * @SuppressWarnings(CamelCaseVariableName)
	 * @SuppressWarnings(LongVariable)
	 */
	public static function initExtension() {
		global $wgPluggableAuth_Class, $wgPluggableAuth_EnableAutoLogin;
		if ( isset( $wgPluggableAuth_Class ) ) {
			return;
		}
		wfDebugLog( __METHOD__, "initializing" );
		$wgPluggableAuth_Class = 'PluggableSSO\PluggableSSO';
		$wgPluggableAuth_EnableAutoLogin = true;
	}
}
