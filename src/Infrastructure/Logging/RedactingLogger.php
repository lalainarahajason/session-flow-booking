<?php
/**
 * Provide the RedactingLogger adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\Infrastructure\Logging;

/** Allowlisted technical context; request bodies and secrets are discarded. */
final class RedactingLogger {
	/**
	 * Keep only diagnostic identifiers and discard request and secret fields.
	 *
	 * @param array $context Untrusted diagnostic context to reduce to allowlisted identifiers.
	 * @return array Operation result according to the adapter contract.
	 */
	public function context( array $context ): array {
		return array_intersect_key( $context, array_flip( array( 'request_id', 'operation_id', 'error_code', 'attempt' ) ) );
	}
}
