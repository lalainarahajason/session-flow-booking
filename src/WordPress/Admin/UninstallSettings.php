<?php
/**
 * Provide the UninstallSettings adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\WordPress\Admin;

/** Explicit administrator choice, protected by intent and capability. */
final class UninstallSettings {
	/**
	 * Verify authority and nonce before persisting the uninstall choice.
	 */
	public function save(): void {
		if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'booking_manage_settings' ) ) {
			wp_die( esc_html__( 'Access denied.', 'session-flow-bookings' ), '', array( 'response' => 403 ) ); }
		check_admin_referer( 'sfbk_save_uninstall' );
		update_option( 'sfbk_delete_data_on_uninstall', isset( $_POST['sfbk_delete_data'] ) && '1' === $_POST['sfbk_delete_data'], false );
		wp_safe_redirect( admin_url( 'admin.php?page=sfbk-settings' ) );
		exit;
	}
}
