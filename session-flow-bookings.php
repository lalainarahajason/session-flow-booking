<?php
/**
 * Plugin Name: Session Flow Bookings
 * Description: Installable infrastructure for training sessions and bookings.
 * Version: 0.1.0
 * Requires at least: 6.8
 * Requires PHP: 8.3
 * Author: Lalaina Rahajason
 * Text Domain: session-flow-bookings
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package SessionFlowBookings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SFBK_VERSION', '0.1.0' );
define( 'SFBK_FILE', __FILE__ );
define( 'SFBK_PATH', __DIR__ . '/' );

if ( ! file_exists( SFBK_PATH . 'vendor/autoload.php' ) ) {
	add_action(
		'admin_notices',
		static function () {
			if ( current_user_can( 'activate_plugins' ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Session Flow Bookings requires its Composer dependencies. Install the complete plugin package.', 'session-flow-bookings' ) . '</p></div>';
			}
		}
	);
	return;
}
require_once SFBK_PATH . 'vendor/autoload.php';
register_activation_hook( __FILE__, array( SessionFlowBookings\WordPress\Lifecycle::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( SessionFlowBookings\WordPress\Lifecycle::class, 'deactivate' ) );
add_action(
	'plugins_loaded',
	static function () {
		( new SessionFlowBookings\Plugin() )->register();
	}
);
