<?php

/**
 * BlueSpice for MediaWiki
 * Extension: Checklist
 * Description: Provides checklist functions.
 * Authors: Markus Glaser
 *
 * Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
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
 * http://www.gnu.org/copyleft/gpl.html
 *
 * For further information visit http://www.blue-spice.org
 *
 * @author     Patric Wirth <wirth@hallowelt.com>
 * @version    2.23.1
 * @package    BlueSpice_Extensions
 * @subpackage Checklist
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
 * @filesource
 */

/**
 * Checklist adds a tag, used in WikiMarkup as follows:
 * checkbox: <bs:checklist />
 */
class Checklist extends BsExtensionMW {

	public $iCheckboxCounter = 0;
	public $bCheckboxFound = false;

	public function __construct() {
		wfProfileIn( 'BS::'.__METHOD__ );

		// Base settings
		$this->mExtensionFile = __FILE__;
		$this->mExtensionType = EXTTYPE::PARSERHOOK; //SPECIALPAGE/OTHER/VARIABLE/PARSERHOOK
		$this->mInfo = array(
			EXTINFO::NAME => 'Checklist',
			EXTINFO::DESCRIPTION => 'bs-checklist-desc',
			EXTINFO::AUTHOR => 'Markus Glaser',
			EXTINFO::VERSION     => 'default',
			EXTINFO::STATUS      => 'default',
			EXTINFO::PACKAGE     => 'default',
			EXTINFO::URL => 'https://help.bluespice.com/index.php/CheckList',
			EXTINFO::DEPS => array( 'bluespice' => '2.22.0' )
		);
		$this->mExtensionKey = 'MW::Checklist';

		wfProfileOut( 'BS::'.__METHOD__ );
	}

	protected function initExt() {
		wfProfileIn( 'BS::'.__METHOD__ );
		$this->setHook( 'ParserFirstCallInit' );
		$this->setHook( 'BeforePageDisplay');
		$this->setHook( 'BSExtendedEditBarBeforeEditToolbar' );
		$this->setHook( 'BSInsertMagicAjaxGetData', 'onBSInsertMagicAjaxGetData' );
		$this->setHook( 'VisualEditorConfig' );
		$this->mCore->registerPermission( 'checklistmodify', array( 'user' ) );
		wfProfileOut( 'BS::'.__METHOD__ );
	}

	/**
	 * Hook Handler for VisualEditorConfig Hook
	 * @param Array $aConfigStandard reference
	 * @param Array $aConfigOverwrite reference
	 * @param Array &$aLoaderUsingDeps reference
	 * @return boolean always true to keep hook alife
	 */
	public function onVisualEditorConfig( &$aConfigStandard, &$aConfigOverwrite, &$aLoaderUsingDeps ) {
		$aLoaderUsingDeps[] = 'ext.bluespice.checklist';

		$iIndexStandard = array_search( 'unlink',$aConfigStandard["toolbar1"] );
		array_splice( $aConfigStandard["toolbar1"], $iIndexStandard + 1, 0, "bscheckbox" );

		// Add context menu entry
		$aConfigStandard["contextmenu"] = str_replace( 'bsContextMenuMarker', 'bsContextMenuMarker bsChecklist', $aConfigStandard["contextmenu"] );
		return true;
	}

	public static function getListOptions( $listTitle ) {
		$aOptions = array();
		$oTitle = Title::newFromText( $listTitle, NS_TEMPLATE );
		//echo $args['list']." ".$oTitle->getArticleID();
		if ( is_object( $oTitle ) ) {
			$oWikiPage = WikiPage::newFromID( $oTitle->getArticleID() );
			if ( is_object( $oWikiPage ) ) {
				$sContent = $oWikiPage->getContent()->getNativeData();
				$aLines = explode( "\n", $sContent );
				foreach ( $aLines as $sLine ) {
					if ( strpos( $sLine, '*' ) !== 0 ) continue;
					$sNewLine = trim( substr( $sLine, 1 ) );
					$aOptions[] = $sNewLine;
				}
			}
		}
		return $aOptions;
	}



	/*http://www.php.net/manual/en/function.preg-replace.php#112400*/
	public static function preg_replace_nth( $pattern, $replacement, $subject, $nth=1 ) {
		return preg_replace_callback( $pattern,
			function( $found ) use ( &$pattern, &$replacement, &$nth ) {
					$nth--;
					if ( $nth==0 ) {
						$sResult = preg_replace( '/value=".*?" /', '', reset( $found ) );
						$sResult = preg_replace( $pattern, $replacement, $sResult );
						return $sResult;
					}
					return reset( $found );
			}, $subject,$nth );
	}

	/**
	 *
	 * @param Parser $parser
	 * @return boolean
	 */
	public function onParserFirstCallInit( &$parser ) {
		$parser->setHook( 'bs:checklist', array( &$this, 'onMagicWordBsChecklist' ) );
		return true;
	}

	public function onBSExtendedEditBarBeforeEditToolbar( &$aRows, &$aButtonCfgs ) {
		$this->getOutput()->addModuleStyles( 'ext.bluespice.checklist.styles' );
		$this->getOutput()->addModules( 'ext.bluespice.checklist' );

		$aRows[0]['dialogs'][60] = 'bs-editbutton-checklist';

		$aButtonCfgs['bs-editbutton-checklist'] = array(
			'tip' => wfMessage( 'bs-checklist-menu-insert-checkbox' )->plain(),
			'open' => '<bs:checklist />'
		);
		return true;
	}

	/**
	 * Inject tags into InsertMagic
	 * @param Object $oResponse reference
	 * $param String $type
	 * @return always true to keep hook running
	 */
	public function onBSInsertMagicAjaxGetData( &$oResponse, $type ) {
		if( $type != 'tags' ) return true;

		$oResponse->result[] = array(
			'id' => 'bs:checklist',
			'type' => 'tag',
			'name' => 'checklist',
			'desc' => wfMessage( 'bs-checklist-tag-checklist-desc' )->text(),
			'code' => '<bs:checklist />',
			'examples' => array(
				array(
					'label' => wfMessage( 'bs-checklist-tag-checklist-example-check' )->text(),
					'code' => '<bs:checklist type="check" value="checked" />'
				),
				array(
					'label' => wfMessage( 'bs-checklist-tag-checklist-example-list' )->text(),
					'code' => '<bs:checklist type="list" value="false" list="Status" />'
				),
			),
			'helplink' => 'https://help.bluespice.com/index.php/Checklist'
		);

		return true;
	}


	public function onMagicWordBsChecklist( $input, $args, $parser ) {
		$parser->disableCache();

		$this->bCheckboxFound = true;
		$sOut = array();

		if ( isset( $args['list'] ) ) {
			$aOptions = $this->getListOptions( $args['list'] );
		}
		if( !isset( $args['value'] ) || $args['value'] === 'false' ) {
			$args['value'] = '';
		}

		$sSelectColor = '';
		if ( isset( $args['type'] ) && $args['type'] == 'list' ) {
			$sOut[] = "<select {color} ";
			$sOut[] = "id='bs-cb-".$this->getNewCheckboxId()."' ";
			$sOut[] = "onchange='BsChecklist.change(this);' ";
			$sOut[] = ">";

			$bDefault = empty( $args['value'] ) ? true : false;

			foreach ( $aOptions as $sOption ) {
				$aOptionSet = explode( "|", $sOption );

				if ( !$sSelectColor && isset( $aOptionSet[1] ) ) {
					$sSelectColor = "style='color:".$aOptionSet[1].";' ";
				}

				$sOption = $aOptionSet[0];
				$sOut[] = "<option ";
				if ( isset( $aOptionSet[1] ) ) {
					$sOut[] = "style='color:".$aOptionSet[1].";' ";
				}

				$sDecodedOption = html_entity_decode( $sOption );
				if ( $bDefault || $args['value'] == $sDecodedOption ) {
					$bDefault = false;
					$sOut[] = "selected='selected'";
					if ( isset( $aOptionSet[1] ) ) {
						$sSelectColor = "style='color:".$aOptionSet[1].";' ";
					}
				}
				$sOut[] = ">";
				$sOut[] = $sOption;
				$sOut[] = "</option>";
			}
			$sOut[] = "</select>";
		} else {
			$sOut[] = "<input type='checkbox' ";
			$sOut[] = "id='bs-cb-".$this->getNewCheckboxId()."' ";
			$sOut[] = "onclick='BsChecklist.click(this);' ";
			if( $args['value'] == 'checked' ) {
				$sOut[] = "checked='checked' ";
			}
			$sOut[] = "/>";
		}
		$sOut = implode( $sOut, '' );
		$sOut = str_replace( '{color}', $sSelectColor, $sOut );
		return $sOut;
	}

	protected function getNewCheckboxId() {
		$this->iCheckboxCounter++;
		return $this->iCheckboxCounter;
	}

	/**
	 * Hook-Handler for MediaWiki 'BeforePageDisplay' hook. Sets context if needed.
	 * @param OutputPage $oOutputPage
	 * @param Skin $oSkin
	 * @return bool
	 */
	public function onBeforePageDisplay( &$oOutputPage, &$oSkin ) {
		// also needed in edit mode
		//if ( $this->bCheckboxFound ) {
			$oOutputPage->addModules( 'ext.bluespice.checklist' );
		//}
		return true;
	}

}
