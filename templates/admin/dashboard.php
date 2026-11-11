<?php
/**
 * Escaped administration template.
 *
 * @package SessionFlowBookings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; }
?>
<div class="wrap sfbk-admin"><h1><?php echo esc_html( $title ); ?></h1><p><?php esc_html_e( 'The technical foundation is installed. Training and booking workflows will be delivered in subsequent increments.', 'session-flow-bookings' ); ?></p></div>
