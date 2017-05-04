<?php

/**
 * @group medium
 * @group API
 * @group Database
 * @group BlueSpice
 * @group BlueSpiceExtensions
 * @group BlueSpiceNamespaceManager
 */
class BSApiNamespaceStoreTest extends BSApiExtJSStoreTestBase {
	protected $iFixtureTotal = 18;

	protected function getStoreSchema () {
		return [
			'id' => [
				'type' => 'integer'
			],
			'name' => [
				'type' => 'string'
			],
			'isSystemNS' => [
				'type' => 'boolean'
			],
			'isTalkNS' => [
				'type' => 'boolean'
			],
			'pageCount' => [
				'type' => 'integer'
			],
			'content' => [
				'type' => 'boolean'
			],
			'subpages' => [
				'type' => 'boolean'
			],
			'searched' => [
				'type' => 'boolean'
			],
		];
	}

	protected function setUp() {
		global $wgContLang;
		parent::setUp();
		$this->setMwGlobals( [
			'wgNamespacesWithSubpages' => [
				99990 => true
			],
			'wgNamespacesToBeSearchedDefault' => [
				99990 => true
			]
		] );
		Hooks::clear( 'LanguageGetNamespaces' );
		Hooks::register( 'LanguageGetNamespaces', function( &$namespaces ){
			$namespaces = [
				-2 => 'Media',
				-1 => 'Special',
				0 => '',
				1 => 'Talk',
				2 => 'User',
				3 => 'User_talk',
				4 => 'Project',
				5 => 'Project_talk',
				6 => 'File',
				7 => 'File_talk',
				8 => 'MediaWiki',
				9 => 'MediaWiki_talk',
				10 => 'Template',
				11 => 'Template_talk',
				12 => 'Help',
				13 => 'Help_talk',
				14 => 'Category',
				15 => 'Category_talk',
				99990 => 'Test',
				99991 => 'Test_talk'
			];
			return true;
		});
		// Clear any previous cached namespaces. Important if caching is enabled
		$wgContLang->resetNamespaces();
	}

	protected function createStoreFixtureData() {
		return true;
	}

	protected function getModuleName () {
		return 'bs-namespace-store';
	}

	public function provideSingleFilterData() {
		return [
			'Filter by isSystemNS' => [ 'boolean', 'eq', 'isSystemNS', false, 2 ]
		];
	}

	public function provideMultipleFilterData () {
		return [
			'Filter by subpages and searched' => [
				[
					[
						'type' => 'boolean',
						'comparison' => 'eq',
						'field' => 'subpages',
						'value' => true
					],
					[
						'type' => 'boolean',
						'comparison' => 'eq',
						'field' => 'searched',
						'value' => true
					]
				],
				1
			]
		];
	}

}

