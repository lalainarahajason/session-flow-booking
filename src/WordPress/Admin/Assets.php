<?php
/**
 * Provide the Assets adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\WordPress\Admin;

/** No product CSS on unrelated WordPress pages. */
final class Assets {
	/**
	 * Load the stylesheet exclusively on product administration screens.
	 *
	 * @param string $hook WordPress administration hook suffix.
	 */
	public function enqueue( string $hook ): void {
		$screen = get_current_screen();
		if ( ! $hook || ! $screen || ! preg_match( '/(?:toplevel_page_|_page_)sfbk-(dashboard|calendar|trainings|sessions|customers|payments|settings)$/D', $screen->id ) ) {
			return; }
		wp_enqueue_style( 'sfbk-admin', plugins_url( 'assets/admin.css', SFBK_FILE ), array(), SFBK_VERSION );
	}
}
