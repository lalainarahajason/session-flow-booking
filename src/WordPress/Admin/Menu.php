<?php
/**
 * Provide the Menu adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\WordPress\Admin;

/** Capability enforcement applies to callbacks as well as menu visibility. */
final class Menu {
	public const PAGES = array(
		'dashboard' => 'read',
		'calendar'  => 'booking_manage_schedule',
		'trainings' => 'booking_manage_catalog',
		'sessions'  => 'booking_manage',
		'customers' => 'booking_manage_customers',
		'payments'  => 'booking_read_finance',
		'settings'  => 'booking_manage_settings',
	);
	/**
	 * Register the adapter callbacks once for the current request.
	 */
	public function register(): void {
		if ( ! $this->can_enter() ) {
			return; }
		add_menu_page( __( 'Session Flow Bookings', 'session-flow-bookings' ), __( 'Session Flow Bookings', 'session-flow-bookings' ), 'read', 'sfbk-dashboard', '', 'dashicons-calendar-alt' );
		$labels = array(
			'dashboard' => __( 'Dashboard', 'session-flow-bookings' ),
			'calendar'  => __( 'Calendar', 'session-flow-bookings' ),
			'trainings' => __( 'Trainings', 'session-flow-bookings' ),
			'sessions'  => __( 'Sessions', 'session-flow-bookings' ),
			'customers' => __( 'Customers', 'session-flow-bookings' ),
			'payments'  => __( 'Payments', 'session-flow-bookings' ),
			'settings'  => __( 'Settings', 'session-flow-bookings' ),
		);
		foreach ( self::PAGES as $page => $capability ) {
			add_submenu_page(
				'sfbk-dashboard',
				$labels[ $page ],
				$labels[ $page ],
				$capability,
				'sfbk-' . $page,
				function () use ( $page, $capability, $labels ) {
					$this->render( $page, $capability, $labels[ $page ] );
				}
			);
		}
	}
	/**
	 * Check whether the current account can enter the administrative shell.
	 *
	 * @return bool Contract result.
	 */
	public function can_enter(): bool {
		if ( current_user_can( 'booking_manage_settings' ) || current_user_can( 'booking_manage' ) ) {
			return true; }
		$user = wp_get_current_user();
		return current_user_can( 'read' ) && in_array( 'sfbk_trainer', $user->roles, true );
	}
	/**
	 * Render the authorized administrative dashboard.
	 */
	public function dashboard(): void {
		$this->render( 'dashboard', 'read', __( 'Dashboard', 'session-flow-bookings' ) ); }
	/**
	 * Verify screen capability and include its allowlisted escaped template.
	 *
	 * @param string $page Allowlisted screen identifier.
	 * @param string $capability Required contract capability.
	 * @param string $title Translated title escaped by the included template.
	 */
	public function render( string $page, string $capability, string $title ): void {
		if ( ! $this->can_enter() || ! current_user_can( $capability ) || ! isset( self::PAGES[ $page ] ) || self::PAGES[ $page ] !== $capability ) {
			wp_die( esc_html__( 'Access denied.', 'session-flow-bookings' ), '', array( 'response' => 403 ) ); }
		if ( '' === $title ) {
			return; }
		$template = 'settings' === $page ? 'settings' : ( 'dashboard' === $page ? 'dashboard' : 'placeholder' );
		require SFBK_PATH . 'templates/admin/' . $template . '.php';
	}
}
