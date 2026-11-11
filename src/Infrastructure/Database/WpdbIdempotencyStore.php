<?php
/**
 * Provide the WpdbIdempotencyStore adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\Infrastructure\Database;

use SessionFlowBookings\Application\Ports\IdempotencyStore;
use SessionFlowBookings\Domain\Principal;
use SessionFlowBookings\Domain\OperationException;

/** Reserve a unique intention inside the mutation transaction. */
final class WpdbIdempotencyStore implements IdempotencyStore {
	/**
	 * Request-local adapter state.
	 *
	 * @var string|null Currently reserved intention identifier.
	 */
	private ?string $record_id = null;
	/**
	 * Bind the explicitly supplied dependencies and immutable context.
	 *
	 * @param \wpdb $db Site-scoped WordPress database connection.
	 */
	public function __construct( private readonly \wpdb $db ) {}
	/**
	 * Serialize an intention and return its authorized completed result if present.
	 *
	 * @param Principal $principal Verified server-side identity and capabilities.
	 * @param string    $operation Stable operation identifier.
	 * @param string    $key UUID intention key.
	 * @param string    $fingerprint Canonical intention digest.
	 * @return array|null Operation result according to the adapter contract.
	 * @throws OperationException If the intention conflicts or is already in progress.
	 * @throws \RuntimeException If the operation cannot satisfy its preconditions or persist its result.
	 */
	public function reserve( Principal $principal, string $operation, string $key, string $fingerprint ): ?array {
		$table = ( new TableNames( $this->db ) )->get( 'idempotency_records' );
		$id    = wp_generate_uuid4();
		$sql   = $this->db->prepare( "INSERT INTO %i (id,site_id,principal_scope,operation_id,request_key,fingerprint,state,lease_token,lease_until,expires_at) VALUES (%s,%d,%s,%s,%s,%s,'running',%s,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 60 SECOND),DATE_ADD(UTC_TIMESTAMP(),INTERVAL 24 HOUR)) ON DUPLICATE KEY UPDATE id=id", $table, $id, $principal->site_id, $principal->scope(), $operation, $key, $fingerprint, wp_generate_uuid4() );
		if ( false === $this->db->query( $sql ) ) {
			throw new OperationException( 'TEMPORARILY_UNAVAILABLE', 503, true ); }
		$record = $this->db->get_row( $this->db->prepare( 'SELECT * FROM %i WHERE site_id=%d AND principal_scope=%s AND operation_id=%s AND request_key=%s FOR UPDATE', $table, $principal->site_id, $principal->scope(), $operation, $key ), ARRAY_A );
		if ( ! $record ) {
			throw new \RuntimeException( 'Missing intention' ); }
		if ( ! hash_equals( $record['fingerprint'], $fingerprint ) ) {
			throw new OperationException( 'IDEMPOTENCY_MISMATCH', 409 ); }
		$this->record_id = $record['id'];
		if ( 'completed' === $record['state'] ) {
			return json_decode( $record['result_reference'], true, 512, JSON_THROW_ON_ERROR ); }
		if ( $record['id'] !== $id ) {
			throw new OperationException( 'REQUEST_IN_PROGRESS', 409, true ); }
		return null;
	}
	/**
	 * Persist the result of the currently reserved intention.
	 *
	 * @param string $key UUID intention key.
	 * @param array  $result Explicit result projection to retain for authorized replay.
	 * @throws \RuntimeException If the operation cannot satisfy its preconditions or persist its result.
	 */
	public function complete( string $key, array $result ): void {
		$table = ( new TableNames( $this->db ) )->get( 'idempotency_records' );
		if ( 1 !== $this->db->update(
			$table,
			array(
				'state'            => 'completed',
				'result_reference' => wp_json_encode( $result ),
			),
			array(
				'id'          => $this->record_id,
				'request_key' => $key,
			),
			array( '%s', '%s' ),
			array( '%s', '%s' )
		) ) {
			throw new \RuntimeException( 'Intention completion failed' ); }
	}
}
