<?php
/**
 * Provide the Capabilities adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\WordPress;

/** Capabilities retain the contract names; role slugs belong to this plugin. */
final class Capabilities {
	public const ALL = array( 'booking_manage', 'booking_manage_catalog', 'booking_manage_schedule', 'booking_manage_customers', 'booking_manage_orders', 'booking_read_finance', 'booking_record_payments', 'booking_refund', 'booking_manage_settings', 'booking_export' );
	/**
	 * Install the default roles and explicit capability grants.
	 */
	public static function install(): void {
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( self::ALL as $capability ) {
					$admin->add_cap( $capability ); }
		}
		add_role( 'sfbk_manager', __( 'Booking manager', 'session-flow-bookings' ), array( 'read' => true ) );
		add_role( 'sfbk_trainer', __( 'Trainer', 'session-flow-bookings' ), array( 'read' => true ) );
		$manager = get_role( 'sfbk_manager' );
		foreach ( array_diff( self::ALL, array( 'booking_record_payments', 'booking_refund', 'booking_manage_settings' ) ) as $capability ) {
			$manager->add_cap( $capability ); }
	}
}
