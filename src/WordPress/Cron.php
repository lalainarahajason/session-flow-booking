<?php
/**
 * Provide the Cron adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\WordPress;

use SessionFlowBookings\Application\JobWorker;
use SessionFlowBookings\Infrastructure\Database\WpdbJobStore;
use SessionFlowBookings\Infrastructure\Database\WpdbTransactionManager;

/** One scheduled event; bounded work and no public worker endpoint. */
final class Cron {
	/**
	 * Schedule the worker only if no event already exists.
	 */
	public static function schedule(): void {
		if ( ! wp_next_scheduled( 'sfbk_worker_tick' ) ) {
			wp_schedule_event( time() + 60, 'hourly', 'sfbk_worker_tick' ); }
	}
	/**
	 * Run a bounded worker batch with durable job leases.
	 */
	public static function tick(): void {
		global $wpdb;
		( new JobWorker( new WpdbTransactionManager( $wpdb ), new WpdbJobStore( $wpdb ) ) )->tick();
	}
}
