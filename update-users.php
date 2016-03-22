<?php

/**
 * This script checks to see if the users added via SSO still exist in
 * the SSO source.  If not it will remove them.
 *
 * Usage:
 *  no parameters
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @author Mark A. Hershberger * @ingroup Maintenance
 */


// Allow people to have different layouts.
if ( ! isset( $IP ) ) {
	$IP = __DIR__ . '/../..';
	if ( getenv("MW_INSTALL_PATH") ) {
		$IP = getenv("MW_INSTALL_PATH");
	}
}

require_once( "$IP/maintenance/Maintenance.php" );

class PruneUsers extends Maintenance {
	protected $deleted = 0;
	protected $deleteFailed = 0;
	protected $skipped = 0;
	protected $kept = 0;
    protected $toDelete = [];

	public function __construct() {
		parent::__construct();

		$this->mDescription = "Prune users from database that " .
			"that no longer exist in the SSO source";
	}

	public function execute() {
		global $wgAuth, $wgUser;
		$dbw = wfGetDB( DB_SLAVE );

		$users = $dbw->select(
			'user',
			array(
				'user_id',
			)
		);
		ConocoPhillips::onPluggableSSOAuth( $wgAuth );
		while ( $dbUser = $users->fetchObject() ) {
			$wgUser = User::newFromId( $dbUser->user_id );
			$wgAuth->resetADData();
			$this->output( "Checking $wgUser ... " );
			$keep = false;

			if ( $this->userStillExists( $wgUser ) ) {
				$this->output( "keeping " );
				$keep = true;
			}
			if ( !$keep && $this->canRemoveUser( $wgUser ) ) {
				$this->output( "will delete" );
                $this->toDelete[] = $wgUser->getID();
			} else {
				$this->output( "skipped" );
				$this->skipped++;
			}
			$this->output( "\n" );
		}

		$total = $this->deleted + $this->deleteFailed + $this->skipped;
		$this->output( "\nFinished checking users.\n" );
		$this->output( "     Total Users: $total\n" );
		$this->output( "            kept: {$this->kept}\n" );
		$this->output( "         skipped: {$this->skipped}\n" );
		$this->output( "         deleted: {$this->deleted}\n" );
		$this->output( "failed to delete: {$this->deleteFailed}\n" );
	}

	public function userStillExists( User $user ) {
		global $wgAuth;

		try {
			$data = $wgAuth->getAdData();
		} catch ( MWException $e ) {
			return false;
		}
		$this->kept++;
		return true;
	}

	public function canRemoveUser( User $user ) {
		if ( $user->getId() == 1 ) {
			return false;
		}
		return RemoveUnusedAccounts::isInactiveAccount( $user->getId() );
	}

	public function deleteUser( User $user ) {
		$this->deleteFailed++;
		return false;
	}
}

$maintClass = "PruneUsers";
require_once( DO_MAINTENANCE );
