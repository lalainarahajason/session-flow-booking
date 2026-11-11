<?php
/**
 * Escaped administration template.
 *
 * @package SessionFlowBookings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; }
?>
<div class="wrap sfbk-admin"><h1><?php echo esc_html( $title ); ?></h1>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
<input type="hidden" name="action" value="sfbk_save_uninstall">
<?php wp_nonce_field( 'sfbk_save_uninstall' ); ?>
<label><input type="checkbox" name="sfbk_delete_data" value="1" <?php checked( (bool) get_option( 'sfbk_delete_data_on_uninstall', false ) ); ?>> <?php esc_html_e( 'Permanently delete plugin data when uninstalling. Disabled by default.', 'session-flow-bookings' ); ?></label>
<?php submit_button(); ?>
</form></div>
