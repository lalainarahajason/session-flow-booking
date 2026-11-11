<?php
/**
 * Provide the TableNames adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\Infrastructure\Database;

/** Fixed structural names, scoped to the WordPress site prefix. */
final class TableNames {
	public const SUFFIXES = array( 'idempotency_records', 'provider_inbox', 'domain_outbox', 'jobs', 'audit_events' );
	/**
	 * Bind the explicitly supplied dependencies and immutable context.
	 *
	 * @param \wpdb $db Site-scoped WordPress database connection.
	 */
	public function __construct( private readonly \wpdb $db ) {}
	/**
	 * Resolve an allowlisted structural name in the current site.
	 *
	 * @param string $suffix Allowlisted product table suffix.
	 * @return string Operation result according to the adapter contract.
	 * @throws \InvalidArgumentException If the operation cannot satisfy its preconditions or persist its result.
	 */
	public function get( string $suffix ): string {
		if ( ! in_array( $suffix, self::SUFFIXES, true ) ) {
			throw new \InvalidArgumentException( 'Unknown table' ); }
		return $this->db->prefix . 'sfbk_' . $suffix;
	}
}
