<?php
/**
 * Provide the Plugin adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings;

use SessionFlowBookings\WordPress\Admin\Menu;
use SessionFlowBookings\WordPress\Admin\Assets;
use SessionFlowBookings\WordPress\Admin\UninstallSettings;
use SessionFlowBookings\WordPress\Cron;
use SessionFlowBookings\Infrastructure\Database\MigrationRunner;

/** Composition root; technical hooks registered once per request. */
final class Plugin {
	/**
	 * Request-local adapter state.
	 *
	 * @var bool Whether hooks were registered for this request.
	 */
	private static bool $registered = false;
	/**
	 * Register the adapter callbacks once for the current request.
	 */
	public function register(): void {
		if ( self::$registered || is_multisite() ) {
			return; }
		self::$registered = true;
		global $wpdb;
		if ( MigrationRunner::VERSION !== get_option( 'sfbk_schema_version', '' ) || get_option( 'sfbk_schema_error' ) ) {
			try {
				( new MigrationRunner( $wpdb ) )->run(); } catch ( \Throwable $error ) {
						add_action(
							'admin_notices',
							static function () {
								if ( current_user_can( 'activate_plugins' ) ) {
									echo '<div class="notice notice-error"><p>' . esc_html__( 'Session Flow Bookings requires a successful database migration before it can run.', 'session-flow-bookings' ) . '</p></div>'; }
							}
						);
						return;
				}
		}
		$menu = new Menu();
		add_action( 'admin_menu', array( $menu, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( new Assets(), 'enqueue' ) );
		add_action( 'admin_post_sfbk_save_uninstall', array( new UninstallSettings(), 'save' ) );
		add_action( 'sfbk_worker_tick', array( Cron::class, 'tick' ) );
	}
}
