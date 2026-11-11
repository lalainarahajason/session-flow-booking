<?php
/**
 * Provide the Lifecycle adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\WordPress;

use SessionFlowBookings\Infrastructure\Database\MigrationRunner;

/** Installation never deletes data and refuses unsupported network activation. */
final class Lifecycle {
	/**
	 * Verify activation authority and establish the schema before scheduling work.
	 *
	 * @param bool $network_wide Whether network activation was requested.
	 */
	public static function activate( bool $network_wide = false ): void {
		if ( $network_wide || is_multisite() ) {
			wp_die( esc_html__( 'This increment requires a single WordPress site.', 'session-flow-bookings' ) ); }
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( esc_html__( 'You are not allowed to activate this plugin.', 'session-flow-bookings' ) ); }
		global $wpdb;
		( new MigrationRunner( $wpdb ) )->run();
		Capabilities::install();
		Cron::schedule();
	}
	/**
	 * Clear scheduled work while preserving all persistent data.
	 */
	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'sfbk_worker_tick' ); }
}
