<?php
/**
 * PageTemplates extension for BlueSpice
 *
 * Displays a list of templates marked as page templates when creating a new article.
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
 * This file is part of BlueSpice for MediaWiki
 * For further information visit http://www.bluespice.com
 *
 * @author     Markus Glaser <glaser@hallowelt.com>
 * @author     Stephan Muggli <muggli@hallowelt.com>
 * @version    2.27.0
 * @package    BlueSpice_Extensions
 * @subpackage PageTemplates
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
 * @filesource
 */

/**
 * Base class for PageTemplates extension
 * @package BlueSpice_Extensions
 * @subpackage PageTemplates
 */
class PageTemplates extends BsExtensionMW {

	public function __construct() {
		wfProfileIn( 'BS::'.__METHOD__ );
		WikiAdmin::registerModuleClass( 'PageTemplatesAdmin', array(
			'image' => '/extensions/BlueSpiceExtensions/WikiAdmin/resources/images/bs-btn_templates_v1.png',
			'level' => 'wikiadmin',
			'message' => 'bs-pagetemplatesadmin-label',
			'iconCls' => 'bs-icon-clipboard-checked'
		) );
		wfProfileOut( 'BS::'.__METHOD__ );
	}
	/**
	 * Initialization of PageTemplates extension
	 */
	protected function initExt() {
		wfProfileIn( 'BS::'.__METHOD__ );

		//Hooks
		$this->setHook( 'LinkBegin' );
		$this->setHook( 'EditPage::showEditForm:initial', 'onEditPageShowEditFormInitial' );
		$this->setHook( 'MessagesPreLoad' );
		$this->setHook( 'ParserFirstCallInit' );

		// Do not use page template mechanism for these pages
		BsConfig::registerVar( 'MW::PageTemplates::ExcludeNs', array( -2,-1,6,7,8,9,10,11,14,15 ), BsConfig::LEVEL_PUBLIC|BsConfig::TYPE_ARRAY_INT|BsConfig::USE_PLUGIN_FOR_PREFS, 'bs-pagetemplates-pref-excludens', 'multiselectex' );
		// Force page to be created in target namespace
		BsConfig::registerVar( 'MW::PageTemplates::ForceNamespace', false, BsConfig::LEVEL_PUBLIC|BsConfig::TYPE_BOOL, 'bs-pagetemplates-pref-forcenamespace', 'toggle' );
		// Hide template if page is not in target namespace
		BsConfig::registerVar( 'MW::PageTemplates::HideIfNotInTargetNs', true, BsConfig::LEVEL_PUBLIC|BsConfig::TYPE_BOOL, 'bs-pagetemplates-pref-hideifnotintargetns', 'toggle' );
		BsConfig::registerVar( 'MW::PageTemplates::HideDefaults', false, BsConfig::LEVEL_PUBLIC|BsConfig::TYPE_BOOL, 'bs-pagetemplates-pref-hidedefaults', 'toggle' );

		$this->mCore->registerPermission( 'pagetemplatesadmin-viewspecialpage', array( 'sysop' ), array( 'type' => 'global' ) );

		wfProfileOut( 'BS::'.__METHOD__ );
	}

	/**
	 * Hook-Handler for Hook 'LoadExtensionSchemaUpdates'
	 * @param object §updater Updater
	 * @return boolean Always true
	 */
	public static function getSchemaUpdates( $updater ) {
		$updater->addExtensionTable(
			'bs_pagetemplate',
			__DIR__.'/'.'db'.'/'.'PageTemplates.sql'
		);
		return true;
	}

	public function runPreferencePlugin( $sAdapterName, $oVariable ) {
		$aNamespaces = array();
		global $wgContLang;

		foreach ( $wgContLang->getNamespaces() as $ns ) {
			$nsIndex = $wgContLang->getNsIndex( $ns );
			$aNamespaces[$nsIndex] = BsNamespaceHelper::getNamespaceName( $nsIndex );
		}

		$aPrefs = array(
			'type' => 'multiselectex',
			'options' => $aNamespaces,
		);
		return $aPrefs;
	}

	/**
	 * Automatically modifies "noarticletext" message. Otherwise, you would
	 * have to modify MediaWiki:noarticletext in the wiki, wich causes
	 * installation overhead.
	 * @param string $sKey The message key. Note that it comes ucfirst and can be an i18n version (e.g. Noarticletext/de-formal)
	 * @param string $sMessage This variable is called by reference and modified.
	 * @return bool Success marker for MediaWiki Hooks. The message itself is returned in referenced variable $sMessage. Note that it cannot contain pure HTML.
	 * @throws PermissionsError
	 */
	public function onMessagesPreLoad( $sKey, &$sMessage ) {
		if ( strstr( $sKey, 'Noarticletext' ) === false ) {
			return true;
		}

		$oTitle = $this->getTitle();
		if ( !is_object( $oTitle ) ) {
			return true;
		}

		/*
		 * As we are in view mode but we present the user only links to
		 * edit/create mode we do a preemptive check wether or not th user
		 * also has edit/create permission
		 */
		if ( $oTitle->isSpecialPage() ) {
			return true;
		}
		if ( !$oTitle->userCan( 'edit' ) ) {
			throw new PermissionsError( 'edit' );
		} elseif ( !$oTitle->userCan( 'createpage' ) ) {
			throw new PermissionsError( 'createpage' );
		} else {
			$sMessage = '<bs:pagetemplates />';
		}

		return true;
	}

	/**
	 * Registers the pagetemplate tag with the parser
	 * @param Parser $parser The parser object of MediaWiki
	 * @return bool allow other hooked methods to be executed. Always true.
	 */
	public function onParserFirstCallInit( &$parser ) {
		$parser->setHook( 'pagetemplates', array( $this, 'onTagPageTemplates' ) );
		$parser->setHook( 'bs:pagetemplates', array( $this, 'onTagPageTemplates' ) );
		return true;
	}

	/**
	 * Callback function that is triggered when the parser encounters a pagetemplate tag
	 * @param string $input innerHTML of the tag
	 * @param array $args tag attributes
	 * @param Parser $parser the parser object of MediaWiki
	 * @return string replacement HTML for the tag
	 */
	public function onTagPageTemplates( $input, $args, $parser ) {
		$parser->getOutput()->addModules( 'ext.bluespice.pageTemplates.tag' );
		$parser->getOutput()->addModuleStyles( 'ext.bluespice.pageTemplates.styles' );
		return $this->renderPageTemplates();
	}

	/**
	 * Renders the pagetemplates form which is displayed when creating a new article
	 * @param bool $bReturnHTML If set, the form is returned as HTML, otherwise as wiki code.
	 * @return string The rendered output
	 */
	protected function renderPageTemplates() {
		$oTitle = $this->getTitle();
		// if we are not on a wiki page, return. This is important when calling import scripts that try to create nonexistent pages, e.g. importImages
		if ( !is_object( $oTitle ) ) return true;

		$oPageTemplateList = new BSPageTemplateList( $oTitle, array(
			BSPageTemplateList::HIDE_IF_NOT_IN_TARGET_NS => BsConfig::get( 'MW::PageTemplates::HideIfNotInTargetNs' ),
			BSPageTemplateList::FORCE_NAMESPACE => BsConfig::get( 'MW::PageTemplates::ForceNamespace' ),
			BSPageTemplateList::HIDE_DEFAULTS => BsConfig::get( 'MW::PageTemplates::HideDefaults' )
		) );

		$oPageTemplateListRenderer = new BSPageTemplateListRenderer();
		Hooks::run( 'BSPageTemplatesBeforeRender', [ $this, &$oPageTemplateList, &$oPageTemplateListRenderer, $oTitle ] );
		return $oPageTemplateListRenderer->render( $oPageTemplateList );
	}

	/**
	 * Hook handler for LinkBegin
	 * @param Linker $oLinker
	 * @param Title $oTarget
	 * @param string $sHtml
	 * @param array $aCustomAttribs
	 * @param array $aQuery
	 * @param array $aOptions
	 * @param string $sRet
	 * @return boolean Always true to keep hook running
	 */
	public function onLinkBegin( $oLinker, $oTarget, &$sHtml, &$aCustomAttribs, &$aQuery, &$aOptions, &$sRet ) {
		if ( in_array( 'known', $aOptions, true ) ) return true;
		if ( !in_array( 'broken', $aOptions, true ) ){ //It's not marked as "known" and not as "broken" so we have to check
			if ( $oTarget->isKnown() ) return true;
		}

		$aExNs = BsConfig::get( 'MW::PageTemplates::ExcludeNs' );
		if ( in_array( $oTarget->getNamespace(), $aExNs ) ) {
			return true;
		}

		if ( !isset( $aQuery['preload'] ) ) {
			$aQuery['action'] = 'view';
		}

		return true;
	}

	/**
	 * Removes noinclude parts from templates
	 * @param EditPage $oEdit MediaWiki EditPage object
	 * @return bool allow other hooked methods to be executed. Always true.
	 */
	function onEditPageShowEditFormInitial( &$oEdit ) {
		if ( RequestContext::getMain()->getRequest()->getVal( 'preload', '' ) ) {
			// TODO MRG (27.09.11 10:28): Put replacement of noinclude into core
			$oEdit->textbox1 = preg_replace( '/<noinclude>.*?<\/noinclude>/s', '',  $oEdit->textbox1 );
		}

		return true;
	}

	/**
	 * Register tag with UsageTracker extension
	 * @param array $aCollectorsConfig
	 * @return Always true to keep hook running
	 */
	public static function onBSUsageTrackerRegisterCollectors( &$aCollectorsConfig ) {
		$aCollectorsConfig['pagetemplates:templates'] = array(
			'class' => 'Database',
			'config' => array(
				'identifier' => 'bs-usagetracker-pagetemplates',
				'descKey' => 'bs-usagetracker-pagetemplates',
				'table' => 'bs_pagetemplate',
				'uniqueColumns' => array( 'pt_id' )
			)
		);
		return true;
	}

	/**
	 * Register PHP Unit Tests with MediaWiki framework
	 * @param array $paths
	 * @return boolean
	 */
	public static function onUnitTestsList( &$paths ) {
		$paths[] =  __DIR__ . '/tests/phpunit/';
		return true;
	}
}
