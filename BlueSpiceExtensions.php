<?php

/* Note: This is a testing entry point not to be used production wise.
 * Do not edit this file manually! Use a dedicated
 * 'BlueSpiceExtensions.local.php' file if you wish to change the
 * settings. You should copy 'BlueSpiceExtensions.default.php' as a
 * starting point
 */

if ( file_exists( __DIR__ . '/vendor/hallowelt/bluespice-foundation/BlueSpiceFoundation.php' ) ) {
	require_once __DIR__ . '/vendor/hallowelt/bluespice-foundation/BlueSpiceFoundation.php';
} elseif ( file_exists( __DIR__ . '/../../vendor/hallowelt/bluespice-foundation/BlueSpiceFoundation.php' ) ) {
	require_once __DIR__ . '/../../vendor/hallowelt/bluespice-foundation/BlueSpiceFoundation.php';
}

if ( file_exists( __DIR__ . '/BlueSpiceExtensions.local.php' ) ) {
	require_once __DIR__ . '/BlueSpiceExtensions.local.php';
} else {
	require_once __DIR__ . '/BlueSpiceExtensions.default.php';
}
