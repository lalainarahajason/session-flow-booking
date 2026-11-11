<?php
/**
 * Explicit, conservative uninstall entry point.
 *
 * @package SessionFlowBookings
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
require_once __DIR__ . '/src/WordPress/Uninstaller.php';
SessionFlowBookings\WordPress\Uninstaller::run();
