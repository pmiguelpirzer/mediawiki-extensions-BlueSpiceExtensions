<?php
/**
 * StateBar extension for BlueSpice
 *
 * Provides a statebar.
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
 * @author     Robert Vogel <vogel@hallowelt.biz>
 * @author     Patric Wirth <wirth@hallowelt.biz>
 * @version    2.22.0
 * @package    BlueSpice_Extensions
 * @subpackage Authors
 * @copyright  Copyright (C) 2011 Hallo Welt! - Medienwerkstatt GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
 * @filesource
 */

/* Changelog
 * v1.20.0
 * - MediaWiki I18N
 * v1.0.0
 * - Raised to stable
 * - Added Events
 * v0.1.0b
 * - FIRST BUILD
 */

// Last review MRG (30.06.11 11:10)

/**
 * Base class for StateBar extension
 * @package BlueSpice_Extensions
 * @subpackage StateBar
 */
class StateBar extends BsExtensionMW {

	protected $aTopViews  = array();
	protected $aBodyViews = array();

	protected $aSortTopVars = array();
	protected $aSortBodyVars = array();

	protected $oRedirectTargetTitle = null;

	/**
	 * Contructor of the StateBar class
	 */
	public function __construct() {
		wfProfileIn( 'BS::'.__METHOD__ );

		// Base settings
		$this->mExtensionFile = __FILE__;
		$this->mExtensionType = EXTTYPE::OTHER;
		$this->mInfo = array(
			EXTINFO::NAME        => 'StateBar',
			EXTINFO::DESCRIPTION => 'Provides a statebar.',
			EXTINFO::AUTHOR      => 'Robert Vogel, Patric Wirth',
			EXTINFO::VERSION     => 'default',
			EXTINFO::STATUS      => 'default',
			EXTINFO::PACKAGE     => 'default',
			EXTINFO::URL         => 'http://www.hallowelt.biz',
			EXTINFO::DEPS        => array( 'bluespice' => '2.22.0' )
		);
		$this->mExtensionKey = 'MW::StateBar';
		wfProfileOut( 'BS::'.__METHOD__ );
	}

	/**
	 * Initialization of StateBar extension
	 */
	protected function initExt() {
		wfProfileIn( 'BS::'.__METHOD__ );

		$this->setHook( 'ParserFirstCallInit' );
		$this->setHook( 'BeforePageDisplay' );
		$this->setHook( 'BSBlueSpiceSkinBeforeArticleContent' );
		$this->setHook( 'BSInsertMagicAjaxGetData' );

		BsConfig::registerVar( 'MW::StateBar::Show', true, BsConfig::LEVEL_PUBLIC|BsConfig::TYPE_BOOL, 'bs-statebar-pref-donotshow', 'toggle' );

		/*Deprecated*/BsConfig::registerVar( 'MW::StateBar::DisableOnPages', '', BsConfig::LEVEL_PRIVATE|BsConfig::TYPE_STRING, 'bs-statebar-pref-disableonpages' );
		/*Deprecated*/BsConfig::registerVar( 'MW::StateBar::DisableForSysops', false, BsConfig::LEVEL_PRIVATE|BsConfig::TYPE_BOOL, 'bs-statebar-pref-disableforsysops', 'toggle' );

		$this->mCore->registerBehaviorSwitch( 'NOSTATEBAR', array( $this, 'noStateBarCallback' ) );

		wfProfileOut( 'BS::'.__METHOD__ );
	}

	/**
	 * Registers StateBar sort variables
	 */
	private function registerSortVars() {
		wfRunHooks( 'BSStateBarAddSortTopVars', array( &$this->aSortTopVars ) );
		$aDefaultSortTopVars = array(
			'statebartopresponsibleeditorsentries' => '',
			'statebartopreview' => '',
			'statebartopsaferedit' => '',
			'statebartopsafereditediting' => '',
			'statebartoplastedited' => '',
			'statebartoplasteditor' => '',
			'statebartopcategories' => '',
			'statebartopsubpages' => '',
		);
		$this->aSortTopVars = array_merge( $aDefaultSortTopVars, $this->aSortTopVars );
		$this->aSortTopVars = array_filter( $this->aSortTopVars ); //removes entries without value
		BsConfig::registerVar( 'MW::StateBar::SortTopVars', $this->aSortTopVars , BsConfig::LEVEL_PUBLIC | BsConfig::TYPE_ARRAY_INT | BsConfig::USE_PLUGIN_FOR_PREFS, 'bs-statebar-pref-sorttopvars', 'multiselectsort' );

		wfRunHooks( 'BSStateBarAddSortBodyVars', array( &$this->aSortBodyVars ) );
		$aDefaultSortBodyVars = array (
			'statebarbodyresponsibleeditorsentries' => '',
			'statebarbodyreview' => '',
			'statebarbodyeditsummary' => '',
			'statebarbodysubpages' => '',
			'statebarbodycategories' => '',
		);
		$this->aSortBodyVars = array_merge( $aDefaultSortBodyVars, $this->aSortBodyVars );
		$this->aSortBodyVars = array_filter( $this->aSortBodyVars ); //removes entries without value
		BsConfig::registerVar( 'MW::StateBar::SortBodyVars', $this->aSortBodyVars , BsConfig::LEVEL_PUBLIC | BsConfig::TYPE_ARRAY_INT | BsConfig::USE_PLUGIN_FOR_PREFS, 'bs-statebar-pref-sortbodyvars', 'multiselectsort' );
	}

	/**
	 * Sets parameters for more complex options in preferences
	 * @param string $sAdapterName Name of the adapter, e.g. MW
	 * @param BsConfig $oVariable Instance of variable
	 * @return array Preferences options
	 */
	public function runPreferencePlugin( $sAdapterName, $oVariable ) {
		wfProfileIn( 'BS::' . __METHOD__ );

		$aPrefs = array();

		switch ($oVariable->getName()) {
			case 'SortTopVars':
				$aPrefs['type']    = 'multiselectsort';
				$aPrefs['options'] = $this->aSortTopVars;
				break;
			case 'SortBodyVars':
				$aPrefs['type']    = 'multiselectsort';
				$aPrefs['options'] = $this->aSortBodyVars;
				break;
		}

		wfProfileOut( 'BS::' . __METHOD__ );

		return $aPrefs;
	}

	/**
	 * Callback for behaviorswitch
	 */
	public function noStateBarCallback() {
		if ( $this->getRequest()->getVal( 'action', 'view' ) === 'edit' ) return;

		BsExtensionManager::setContext( 'MW::StateBar:Hide' );
	}

	/**
	 * AJAX interface for BlueSpice SateBar body views
	 * @return string The JSON formatted response
	 */
	public static function ajaxCollectBodyViews() {
		global $wgUser;
		$aResult = array(
			"success" => false,
			"views" => array(),
			"message" => '',
		);

		$iArticleID = RequestContext::getMain()->getRequest()->getInt( 'articleID', 0 );
		if( empty($iArticleID) ) {
			return json_encode($aResult);
		}

		$oStateBar = BsExtensionManager::getExtension( 'StateBar' );
		$oStateBar->registerSortVars();

		$oTitle = $oStateBar->checkContext( 
			Title::newFromID( $iArticleID ),
			true //because you already have the possible redirected title!
				 //also prevents from get wrong data in redirect redirect
		);
		if( is_null($oTitle) ) {
			return json_encode( $aResult );
		}

		$aBodyViews = array();
		wfRunHooks( 'BSStateBarBeforeBodyViewAdd', array( $oStateBar, &$aBodyViews, $wgUser, $oTitle ) );
		if( empty($aBodyViews) ) {
			$aResult['success'] = true;
			$aResult['message'] = wfMessage('bs-statebar-ajax-nobodyviews')->plain();
			return json_encode( $aResult );
		}

		$aSortBodyVars = BsConfig::get('MW::StateBar::SortBodyVars');
		if( !empty($aSortBodyVars) ) {
			$aBodyViews = $oStateBar->reorderViews( $aBodyViews, $aSortBodyVars );
		}

		//execute all views to an array with numeric index
		$aExecutedBodyViews = array();
		foreach( $aBodyViews as $oView ) $aExecutedBodyViews[] = $oView->execute();

		$aResult['views'] = $aExecutedBodyViews;
		$aResult['success'] = true;
		return json_encode( $aResult );
	}

	/**
	 * Inject tags into InsertMagic
	 * @param Object $oResponse reference
	 * $param String $type
	 * @return always true to keep hook running
	 */
	public function onBSInsertMagicAjaxGetData( &$oResponse, $type ) {
		if( $type != 'switches' ) return true;

		$oResponse->result[] = array(
			'id'   => 'bs:statebar',
			'type' => 'switch',
			'name' => 'NOSTATEBAR',
			'desc' => wfMessage( 'bs-statebar-switch-description' )->plain(),
			'code' => '__NOSTATEBAR__',
		);

		return true;
	}

	// TODO MRG (06.11.13 21:10): Does this also work in edit mode? It seems, there is no parser
	/**
	 * ParserFirstCallInit Hook is called when the parser initialises for the first time.
	 * @param Parser $parser MediaWiki Parser object
	 * @return bool allow other hooked methods to be executed. Always true.
	 */
	public function onParserFirstCallInit( &$parser ) {
		wfProfileIn( 'BS::'.__METHOD__ );

		$this->registerSortVars();

		wfProfileOut( 'BS::'.__METHOD__ );
		return true;
	}

	/**
	 * Checks wether to set Context or not.
	 * @param Title $oTitle
	 * @param bool $bRedirect
	 * @return Title - null when context check fails
	 */
	private function checkContext( $oTitle, $bRedirect = false ) {
		if ( is_null( $oTitle ) ) return null;
		if ( $oTitle->exists() === false ) return null;
		if ( $oTitle->getNamespace() === NS_SPECIAL ) return null;
		if ( $oTitle->userCan( 'read' ) === false ) return null;

		if ( $bRedirect ) {
			if ( BsExtensionManager::isContextActive( 'MW::StateBar:Hide' ) ) return null;
			return $oTitle;
		}

		global $wgRequest;
		if ( $oTitle->isRedirect() && $wgRequest->getVal( 'redirect' ) != 'no' ) {
			//check again for redirect target
			$oTitle = BsArticleHelper::getInstance( $oTitle )->getTitleFromRedirectRecurse();
			$this->oRedirectTargetTitle = $oTitle;
			if ( $oTitle->exists() ) {
				return $this->checkContext( $oTitle, true );
			} else {
				/* if redirect points to none existing article
				   you don't get redirected, so display StateBar
				   HW#2014010710000128 */
				return true;
			}
		}

		if ( BsExtensionManager::isContextActive( 'MW::StateBar:Hide' ) ) return null;
		return $oTitle;
	}

	/**
	 * Hook-Handler for MediaWiki 'BeforePageDisplay' hook. Sets context if needed.
	 * @param OutputPage $oOutputPage
	 * @param Skin $oSkin
	 * @return bool
	 */
	public function onBeforePageDisplay( &$oOutputPage, &$oSkin ) {
		if ( BsConfig::get( 'MW::StateBar::Show' ) === false ) return true;
		//make sure to use wgTitle to get possible redirect as early as possible
		//also prevents from get wrong data in redirect redirect
		$oTitle = $this->checkContext( $this->getTitle() );
		if ( is_null( $oTitle ) ) return true;

		$oOutputPage->addModules( 'ext.bluespice.statebar' );
		$oOutputPage->addModuleStyles( 'ext.bluespice.statebar.style' );

		$aDisableOnSites = explode( ',',BsConfig::get( 'MW::StateBar::DisableOnPages' ) ); //Deprecated
		if ( !empty( $aDisableOnSites ) ) {
			$aUserGroups = $this->getUser()->getGroups();
			if ( !in_array( 'sysop', $aUserGroups ) || BsConfig::get( 'MW::StateBar::DisableForSysops' ) == true ) { //Deprecated
				$aUrlFriendlyTitles = array();
				foreach ( $aDisableOnSites as $sPageTitle ) {
					$aUrlFriendlyTitles[] = str_replace( ' ', '_', trim( $sPageTitle ) );
				}
				$oTitle = $oOutputPage->getTitle();
				$sUrlFriendlyTitle = str_replace( ' ', '_', $oTitle->getPrefixedText() );
				if ( in_array( $sUrlFriendlyTitle, $aUrlFriendlyTitles ) ) return true;
			}
		}

		BsExtensionManager::setContext( 'MW::StateBarShow' );
		return true;
	}

	/**
	 * Hook-Handler for 'BSBlueSpiceSkinBeforeArticleContent'. Creates the StateBar. on articles.
	 * @param array $aViews Array of views to be rendered in skin
	 * @param User $oUser Current user object
	 * @param Title $oTitle Current title object
	 * @return bool Always true to keep hook running.
	 */
	public function onBSBlueSpiceSkinBeforeArticleContent( &$aViews, $oUser, $oTitle, $oSkinTemplate ) {
		if( BsExtensionManager::isContextActive( 'MW::StateBarShow' ) === false ) return true;

		if( !is_null( $this->oRedirectTargetTitle ) ) {
			$oTitle = $this->oRedirectTargetTitle;
		}
		wfRunHooks("BSStateBarBeforeTopViewAdd", array( $this, &$this->aTopViews, $oUser, $oTitle, $oSkinTemplate ));

		if( count( $this->aTopViews ) == 0 ) {
			BsExtensionManager::removeContext( 'MW::StateBarShow' ); // TODO RBV (01.07.11 18:26): Ain't this too late?
			return true;
		}

		$aSortTopVars = BsConfig::get('MW::StateBar::SortTopVars');
		if( !empty( $aSortTopVars ) ) {
			$this->aTopViews = $this->reorderViews( $this->aTopViews, $aSortTopVars );
		}

		$oViewStateBar = new ViewStateBar();
		foreach ( $this->aTopViews as $mKey => $oTopView ) {
			$oViewStateBar->addStateBarTopView( $oTopView );
		}

		$aViews[] = $oViewStateBar;
		return true;
	}

	/**
	 * Private Method to reorder views
	 * @param array $aViews
	 * @param array $aViewSort
	 * @return array
	 */
	public function reorderViews( $aViews, $aViewSort ) {
		$aReorderedViews = array();

		foreach( $aViewSort as $sViewKey ) {
			if( isset($aViews[$sViewKey]) ) {
				$aReorderedViews[] = $aViews[$sViewKey];
				unset( $aViews[$sViewKey] );
			}
		}
		foreach( $aViews as $key => $oView ) {
			$aReorderedViews[$key] = $oView;
		}

		return $aReorderedViews;
	}

	/**
	 * Adder-Method for the internal $aTopView field.
	 * @param ViewStateBarTopElement $oTopView
	 * @param int $iSortId
	 */
	public function addTopView( $oTopView, $iSortId = null ) {
		if ( $iSortId === null ) {
			$this->aTopViews[] = $oTopView;
		} else {
			$this->aTopViews[$iSortId] = $oTopView;
		}
	}

	/**
	 * Adder-Method for the internal $aBodyViews field.
	 * @param ViewStateBarBodyElement $oBodyView
	 * @param int $iSortId
	 */
	public function addBodyView( $oBodyView, $iSortId = null ) {
		if ( $iSortId === null ) {
			$this->aBodyViews[] = $oBodyView;
		} else {
			$this->aBodyViews[$iSortId] = $oBodyView;
		}
	}
}