<?php

BsExtensionManager::registerExtension('HideTitle', BsRUNLEVEL::FULL|BsRUNLEVEL::REMOTE);

$GLOBALS['wgAutoloadClasses']['HideTitle'] = __DIR__ . '/HideTitle.class.php';

$wgMessagesDirs['HideTitle'] = __DIR__ . '/i18n';

$wgExtensionMessagesFiles['HideTitleMagic'] = __DIR__ . '/languages/HideTitle.i18n.magic.php';