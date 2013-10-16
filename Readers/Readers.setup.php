<?php

BsExtensionManager::registerExtension('Readers', BsRUNLEVEL::FULL|BsRUNLEVEL::REMOTE, BsACTION::LOAD_SPECIALPAGE);

$wgExtensionMessagesFiles['Readers'] = __DIR__ . '/languages/Readers.i18n.php';
$wgExtensionMessagesFiles['ReadersAlias'] = __DIR__.'/languages/SpecialReaders.alias.php';

$wgAutoloadClasses['ViewReaders'] = __DIR__ . '/views/view.Readers.php';
$wgAutoloadClasses['SpecialReaders']  = __DIR__.'/includes/specials/SpecialReaders.class.php';

$wgSpecialPages['Readers'] = 'SpecialReaders';

$wgSpecialPageGroups['Readers'] = 'bluespice';

$wgAjaxExportList[] = 'Readers::getUsers';

$aResourceModuleTemplate = array(
	'localBasePath' => $IP.'/extensions/BlueSpiceExtensions/Readers/resources',
	//'remoteBasePath' => &$GLOBALS['wgScriptPath'],
	'remoteExtPath' => 'BlueSpiceExtensions/Readers/resources',
);

$wgResourceModules['ext.bluespice.readers.styles'] = array(
	'styles' => array(
		'bluespice.readers.css'
	),
	'dependencies' => array(
		'ext.bluespice.extjs'
	)
) + $aResourceModuleTemplate;

$wgResourceModules['ext.bluespice.readers.specialreaders'] = array(
	'scripts' => array(
		'extensions/BlueSpiceExtensions/Readers/resources/bluespice.readers.js',
	),
	'dependencies' => array(
		'ext.bluespice.extjs'
	),
	'messages' => array(
		'bs-readers-headerUsername',
		'bs-readers-headerReadersPath',
		'bs-readers-headerTs'
	),
	'localBasePath' => $IP,
	'remoteBasePath' => &$GLOBALS['wgScriptPath']
);