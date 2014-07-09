<?php

BsExtensionManager::registerExtension('WidgetBar', BsRUNLEVEL::FULL|BsRUNLEVEL::REMOTE);

$wgExtensionMessagesFiles['WidgetBar'] = __DIR__ . '/WidgetBar.i18n.php';

$aResourceModuleTemplate = array(
	'localBasePath' => 'extensions/BlueSpiceExtensions/WidgetBar/resources/',
	'remoteExtPath' => 'BlueSpiceExtensions/WidgetBar/resources'
);

$wgResourceModules['ext.bluespice.widgetbar.style'] = array(
	'styles'  => 'bluespice.widgetBar.css'
) + $aResourceModuleTemplate;

$wgResourceModules['ext.bluespice.widgetbar'] = array(
	'scripts' => 'bluespice.widgetBar.js',
	'position' => 'bottom'
) + $aResourceModuleTemplate;

unset( $aResourceModuleTemplate );

$wgAutoloadClasses['ViewWidget']          = __DIR__ . '/views/view.Widget.php';
$wgAutoloadClasses['ViewWidgetError']     = __DIR__ . '/views/view.WidgetError.php';
$wgAutoloadClasses['ViewWidgetErrorList'] = __DIR__ . '/views/view.WidgetErrorList.php';
$wgAutoloadClasses['ViewWidgetList']      = __DIR__ . '/views/view.WidgetList.php';
