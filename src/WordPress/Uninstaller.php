<?php
/**
 * Provide the Uninstaller adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\WordPress;

/** Standalone uninstall; only explicit opt-in permits permanent deletion. */
final class Uninstaller {
	/**
	 * Perform the isolated lifecycle operation.
	 *
	 * @throws \RuntimeException If the lifecycle operation fails.
	 */
	public static function run(): void {
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			return; }
		wp_clear_scheduled_hook( 'sfbk_worker_tick' );
		if ( ! get_option( 'sfbk_delete_data_on_uninstall', false ) ) {
			return; }
		global $wpdb;
		foreach ( array( 'idempotency_records', 'provider_inbox', 'domain_outbox', 'jobs', 'audit_events' ) as $suffix ) {
			$table = $wpdb->prefix . 'sfbk_' . $suffix;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Explicit opt-in uninstall DDL cannot be cached; fixed owned table with identifier placeholder.
			if ( false === $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table ) ) ) {
				return; }
		}
		foreach ( array( 'sfbk_manager', 'sfbk_trainer' ) as $role ) {
			remove_role( $role ); }
		foreach ( wp_roles()->role_objects as $role ) {
			foreach ( array( 'booking_manage', 'booking_manage_catalog', 'booking_manage_schedule', 'booking_manage_customers', 'booking_manage_orders', 'booking_read_finance', 'booking_record_payments', 'booking_refund', 'booking_manage_settings', 'booking_export' ) as $capability ) {
				$role->remove_cap( $capability ); }
		}
		foreach ( array( 'sfbk_schema_version', 'sfbk_schema_error', 'sfbk_migration_checkpoint', 'sfbk_delete_data_on_uninstall' ) as $option ) {
			delete_option( $option ); }
	}
}
