<?php

/**
 * PermissionManager extension for BlueSpice
 *
 * Provides information about an article for status bar.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * This file is part of BlueSpice for MediaWiki
 * For further information visit http://www.blue-spice.org
 *
 * @author     Sebastian Ulbricht <sebastian.ulbricht@gmx.de>
 * @version    2.22.0

 * @package    BlueSpice_Extensions
 * @subpackage PermissionManager
 * @copyright  Copyright (C) 2011 Hallo Welt! - Medienwerkstatt GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
 * @filesource
 */
// Last review MRG (01.07.11 12:51)

/**
 * Class for a temporary user object to check permissions
 * @package BlueSpice_Extensions
 * @subpackage WikiAdmin
 */
class CheckUser extends User {

	public function __construct() {
		parent::__construct();
		$this->mLoadedItems = true;
	}

	public function setGroups( $groups ) {
		if ( !is_array( $groups ) ) {
			$this->mGroups[ ] = $groups;
		} else {
			$this->mGroups = $groups;
		}
		
		// we need a user id if we want to test more than just *
		if ( in_array( '*', $this->mGroups ) && count( $this->mGroups ) == 1 ) {
			$this->mId = 0;
		} else {
			$this->mId = 100;
		}
	}

	public function load() {
		return;
	}

	private function loadGroups() {
		return;
	}

	public function saveSettings() {
		return;
	}

}