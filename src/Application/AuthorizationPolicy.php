<?php
/**
 * Provide the AuthorizationPolicy adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\Application;

use SessionFlowBookings\Domain\OperationException;
use SessionFlowBookings\Domain\Principal;

/** Site and object scope remains mandatory after capability checks. */
final class AuthorizationPolicy {
	/**
	 * Reject objects outside the current site or authorized ownership scope.
	 *
	 * @param Principal $principal Verified server-side identity and capabilities.
	 * @param int       $site_id Server-established site identifier.
	 * @param int       $owner_id Persisted owner of the requested object.
	 * @throws OperationException If the operation cannot satisfy its preconditions or persist its result.
	 */
	public function assert_visible( Principal $principal, int $site_id, int $owner_id ): void {
		if ( $principal->site_id !== $site_id || ( $principal->user_id !== $owner_id && ! in_array( 'booking_manage_settings', $principal->capabilities, true ) ) ) {
			throw new OperationException( 'NOT_FOUND', 404 );
		}
	}
	/**
	 * Require an explicitly granted operation capability.
	 *
	 * @param Principal $principal Verified server-side identity and capabilities.
	 * @param string    $capability Required contract capability.
	 * @throws OperationException If the operation cannot satisfy its preconditions or persist its result.
	 */
	public function assert_capability( Principal $principal, string $capability ): void {
		if ( ! in_array( $capability, $principal->capabilities, true ) ) {
			throw new OperationException( 'FORBIDDEN', 403 ); }
	}
}
