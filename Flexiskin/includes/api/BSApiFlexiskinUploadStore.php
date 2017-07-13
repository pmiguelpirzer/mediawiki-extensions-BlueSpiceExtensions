<?php

/**
 * Provides the flexiskin upload store api for BlueSpice.
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
 *
 * This file is part of BlueSpice MediaWiki
 * For further information visit http://www.bluespice.com
 *
 * @author     Daniel Vogel
 * @package    Bluespice_Extensions
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
 */


class BSApiFlexiskinUploadStore extends BSApiExtJSStoreBase {
	protected function makeData( $sQuery = '' ) {

		global $wgUploadDirectory;
		$aData = array();

		if ( BsFileSystemHelper::hasTraversal( $sQuery ) ) {
			// error message would be nice ;)
			return $aData;
		}

		$flexiskinFiles = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
				$wgUploadDirectory.'/bluespice/flexiskin/' . $sQuery . '/images/',
				( RecursiveIteratorIterator::SELF_FIRST | RecursiveDirectoryIterator::SKIP_DOTS )
			)
		);

		foreach( $flexiskinFiles as $object ){
			if( $object instanceof SplFileInfo );
			$aData[] = (object) array(
				'filename' => $object->getFilename(),
				'extension' => $object->getExtension(),
				'mtime' => $object->getMTime()
			);
		}

		return $aData;
	}
}