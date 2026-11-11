<?php
/**
 * Provide the WpdbAuditStore adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\Infrastructure\Database;

use SessionFlowBookings\Application\Ports\AuditStore;

/** Append-only audit through explicit fields and formats. */
final class WpdbAuditStore implements AuditStore {
	/**
	 * Bind the explicitly supplied dependencies and immutable context.
	 *
	 * @param \wpdb $db Site-scoped WordPress database connection.
	 */
	public function __construct( private readonly \wpdb $db ) {}
	/**
	 * Persist the minimal record in the caller transaction or throw on failure.
	 *
	 * @param array $record Allowlisted audit fields without secrets.
	 * @throws \RuntimeException If the operation cannot satisfy its preconditions or persist its result.
	 */
	public function append( array $record ): void {
		$record                = array_intersect_key( $record, array_flip( array( 'site_id', 'request_id', 'operation_id', 'actor_type', 'actor_id', 'action', 'subject_id', 'version_before', 'version_after' ) ) );
		$record['id']          = wp_generate_uuid4();
		$record['occurred_at'] = gmdate( 'Y-m-d H:i:s' );
		$formats               = array_map( static fn( $value ) => is_int( $value ) ? '%d' : '%s', $record );
		if ( false === $this->db->insert( ( new TableNames( $this->db ) )->get( 'audit_events' ), $record, $formats ) ) {
			throw new \RuntimeException( 'Audit failed' ); }
	}
}
