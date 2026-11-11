<?php
/**
 * Provide the WpdbJobStore adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\Infrastructure\Database;

use SessionFlowBookings\Application\Ports\JobStore;

/** Lease claims and acknowledgements are scoped to site and possession token. */
final class WpdbJobStore implements JobStore {
	/**
	 * Bind the explicitly supplied dependencies and immutable context.
	 *
	 * @param \wpdb $db Site-scoped WordPress database connection.
	 */
	public function __construct( private readonly \wpdb $db ) {}
	/**
	 * Acquire the next eligible job using an expiring possession token.
	 *
	 * @return array|null Contract result.
	 * @throws \RuntimeException If the claim cannot be persisted.
	 */
	public function claim(): ?array {
		return ( new WpdbTransactionManager( $this->db ) )->run(
			function () {
				$table = ( new TableNames( $this->db ) )->get( 'jobs' );
				$job   = $this->db->get_row( $this->db->prepare( "SELECT * FROM %i WHERE site_id=%d AND state IN ('queued','running') AND available_at<=UTC_TIMESTAMP() AND (lease_until IS NULL OR lease_until<=UTC_TIMESTAMP()) ORDER BY available_at,id LIMIT 1 FOR UPDATE SKIP LOCKED", $table, get_current_blog_id() ), ARRAY_A );
				if ( ! $job ) {
					return null; }
				$token = wp_generate_uuid4();
				if ( 1 !== $this->db->query( $this->db->prepare( "UPDATE %i SET state='running',lease_token=%s,lease_until=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 60 SECOND),attempt_count=attempt_count+1 WHERE id=%s", $table, $token, $job['id'] ) ) ) {
					throw new \RuntimeException( 'Claim failed' ); }
				$job['lease_token'] = $token;
				return $job;
			}
		);
	}
	/**
	 * Lock and verify current ownership before applying a consumer effect.
	 *
	 * @param string $id Opaque internal identifier.
	 * @param string $token Current durable lease possession token.
	 * @throws \RuntimeException If the operation cannot satisfy its preconditions or persist its result.
	 */
	public function assert_owned( string $id, string $token ): void {
		$table = ( new TableNames( $this->db ) )->get( 'jobs' );
		$owned = $this->db->get_var( $this->db->prepare( "SELECT id FROM %i WHERE id=%s AND site_id=%d AND lease_token=%s AND state='running' AND lease_until>UTC_TIMESTAMP() FOR UPDATE", $table, $id, get_current_blog_id(), $token ) );
		if ( ! $owned ) {
			throw new \RuntimeException( 'Lease lost' ); }
	}
	/**
	 * Complete only the job still owned by this unexpired token.
	 *
	 * @param string $id Opaque internal identifier.
	 * @param string $token Current durable lease possession token.
	 * @throws \RuntimeException If the operation cannot satisfy its preconditions or persist its result.
	 */
	public function acknowledge( string $id, string $token ): void {
		$table = ( new TableNames( $this->db ) )->get( 'jobs' );
		if ( 1 !== $this->db->query( $this->db->prepare( "UPDATE %i SET state='succeeded',completed_items=total_items,lease_token=NULL,lease_until=NULL WHERE id=%s AND site_id=%d AND lease_token=%s AND state='running' AND lease_until>UTC_TIMESTAMP()", $table, $id, get_current_blog_id(), $token ) ) ) {
			throw new \RuntimeException( 'Lease lost' ); }
	}
	/**
	 * Schedule a bounded retry only while the caller still owns the lease.
	 *
	 * @param string $id Opaque internal identifier.
	 * @param string $token Current durable lease possession token.
	 * @param int    $attempt Number of attempts already made.
	 */
	public function retry( string $id, string $token, int $attempt ): void {
		$delays = array( 60, 300, 1800, 7200, 43200 );
		$table  = ( new TableNames( $this->db ) )->get( 'jobs' );
		$state  = $attempt >= count( $delays ) ? 'failed' : 'queued';
		$delay  = $delays[ min( $attempt, count( $delays ) - 1 ) ];
		$this->db->query( $this->db->prepare( "UPDATE %i SET state=%s,error_code='HANDLER_FAILED',available_at=DATE_ADD(UTC_TIMESTAMP(),INTERVAL %d SECOND),lease_token=NULL,lease_until=NULL WHERE id=%s AND site_id=%d AND lease_token=%s AND lease_until>UTC_TIMESTAMP()", $table, $state, $delay, $id, get_current_blog_id(), $token ) );
	}
}
