<?php


/**
 * BlueSpice for MediaWiki
 * Extension: ContextMenu
 * Description: Provides context menus for various MediaWiki links
 * Authors: Tobias Weichart, Robert Vogel
 *
 * Copyright (C) 2014 Hallo Welt! – Medienwerkstatt GmbH, All rights reserved.
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
 */

class ContextMenu extends BsExtensionMW {

	/**
	 * Contructor of the ContextMenu class
	 */
	public function __construct() {
		wfProfileIn('BS::' . __METHOD__);
		// Base settings
		$this->mExtensionFile = __FILE__;
		$this->mExtensionType = EXTTYPE::PARSERHOOK;
		$this->mInfo = array(
			EXTINFO::NAME => 'ContextMenu',
			EXTINFO::DESCRIPTION => wfMessage('bs-contextmenu-desc')->plain(),
			EXTINFO::AUTHOR => 'Tobias Weichart',
			EXTINFO::VERSION => 'default',
			EXTINFO::STATUS => 'default',
			EXTINFO::PACKAGE => 'default',
			EXTINFO::URL => 'http://www.hallowelt.biz',
			EXTINFO::DEPS => array('bluespice' => '2.23.0')
		);
		$this->mExtensionKey = 'MW::ContextMenu';
		wfProfileOut('BS::' . __METHOD__);
	}

	/**
	 * Initialization of ContextMenu extension
	 */
	protected function initExt() {
		$this->setHook('BeforePageDisplay');
		$this->setHook('LinkerMakeMediaLinkFile');
		$this->setHook('LinkEnd');
		$this->setHook('ThumbnailBeforeProduceHTML');

		BsConfig::registerVar( 'MW::ContextMenu::Modus', 'no-ctrl', BsConfig::LEVEL_USER|BsConfig::TYPE_STRING|BsConfig::USE_PLUGIN_FOR_PREFS, 'bs-contextmenu-pref-modus', 'radio' );
	}

	/**
	 * Called by Preferences and UserPreferences
	 * @param string $sAdapterName Name of the adapter. Probably MW.
	 * @param BsConfig $oVariable The variable that is to be specified.
	 * @return array Option array of specifications.
	 */
	public function runPreferencePlugin( $sAdapterName, $oVariable ) {
		return array(
			'options' => array(
				wfMessage( 'bs-contextmenu-pref-modus-just-right-mouse' )->text() => 'no-crtl',
				wfMessage( 'bs-contextmenu-pref-modus-ctrl-and-right-mouse' )->text() => 'ctrl',
			),
		);
	}

	/**
	 * Adds resources to ResourceLoader
	 * @param OutputPage $out
	 * @param Skin $skin
	 * @return boolean Always true to keep hook running
	 */
	public function onBeforePageDisplay(&$out, &$skin) {
		$out->addModules('ext.bluespice.contextmenu');

		//We check if the current user can send Mails trough the wiki
		//TODO: Maybe move to BSF?
		$mEMailPermissioErrors = SpecialEmailUser::getPermissionsError(
			$this->getUser(), $this->getUser()->getEditToken()
		);

		$bUserCanSendMail = false;
		if ($mEMailPermissioErrors === null) {
			$bUserCanSendMail = true;
		}

		$out->addJsConfigVars( 'bsUserCanSendMail', $bUserCanSendMail );

		return true;
	}

	/**
	 * Adds data attributes to media link tags
	 * THIS IS FOR FUTURE USE: The hook is available starting with MW 1.24!
	 * @param Title $title
	 * @param File $file The File object
	 * @param string $html The content of the resulting  anchor tag
	 * @param array $attribs An array of attributes that will be used in the resulting anchor tag
	 * @param string $ret The HTML output in case the handler returns false
	 * @return boolean Always true to keep hook running
	 */
	public function onLinkerMakeMediaLinkFile( $title, $file, &$html, &$attribs, &$ret ) {

		$attribs['data-bs-title'] = $title->getPrefixedText();
		$attribs['data-bs-filename'] = $file->getName();

		return true;
	}

	/**
	 * Adds additional data to links generated by the framework. This allows us
	 * to add more functionality to the UI.
	 * @param SkinTemplate $skin
	 * @param Title $target
	 * @param array $options
	 * @param string $html
	 * @param array $attribs
	 * @param string $ret
	 * @return boolean Always true to keep hook running
	 */
	public function onLinkEnd( $skin, $target, $options, &$html, &$attribs, &$ret ) {
		if( $target->getNamespace() == NS_USER && $target->isSubpage() === false ) {
			$oUser = User::newFromName($target->getText());
			$sMailAddress = $oUser->getEmail();
			$attribs['data-bs-user-has-email']
				= empty( $sMailAddress ) ? false : true ;

			//This is already in BSF, but it is only included when the anchor
			//content is the same as the username
			$attribs['data-bs-username'] = $target->getText();
		}
		if( $target->getNamespace() >= 0 && $target->isContentPage() ) {
			$attribs['data-bs-is-contentpage'] = true;
		}

		if( $target->getNamespace() === NS_FILE ) {
			$oFile = wfFindFile( $target );

			if( $oFile instanceof File ) {
				$attribs['data-bs-filename'] = $oFile->getName();
				if( $oFile->exists() ) {
					$attribs['data-bs-fileurl'] = $oFile->getUrl();
				}
			}
		}

		return true;
	}

	/**
	 * Adds data attribute to standard image output
	 * @param ThumbnailImage $thumbnail
	 * @param array $attribs
	 * @param array $linkAttribs
	 * @return boolean
	 */
	public function onThumbnailBeforeProduceHTML( $thumbnail, &$attribs, &$linkAttribs ) {
		$oFile = $thumbnail->getFile();
		$linkAttribs['data-bs-filename'] = $oFile->getName();
		$linkAttribs['data-bs-fileurl'] = $oFile->getUrl();
		return true;
	}
}
