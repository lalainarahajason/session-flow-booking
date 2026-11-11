<?php
/**
 * Provide the Version adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\Domain;

/** Strong aggregate version precondition. */
final class Version {
	/**
	 * Parse a strong quoted version and reject missing or invalid preconditions.
	 *
	 * @param string|null $header Strong quoted aggregate version.
	 * @return int Operation result according to the adapter contract.
	 * @throws OperationException If the operation cannot satisfy its preconditions or persist its result.
	 */
	public static function parse( ?string $header ): int {
		if ( null === $header || '' === $header ) {
			throw new OperationException( 'PRECONDITION_REQUIRED', 428 ); }
		if ( ! preg_match( '/^"([1-9][0-9]{0,9})"$/D', $header, $match ) || (int) $match[1] > 2147483647 ) {
			throw new OperationException( 'VALIDATION_ERROR', 422 ); }
		return (int) $match[1];
	}
	/**
	 * Reject a stale aggregate version before a mutation.
	 *
	 * @param int $expected Version supplied by the authorized client.
	 * @param int $actual Version read under the aggregate lock.
	 * @throws OperationException If the operation cannot satisfy its preconditions or persist its result.
	 */
	public static function assert( int $expected, int $actual ): void {
		if ( $expected !== $actual ) {
			throw new OperationException( 'VERSION_MISMATCH', 412 ); }
	}
}
