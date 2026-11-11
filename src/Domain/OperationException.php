<?php
/**
 * Provide the OperationException adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\Domain;

/** Expected application failure without private diagnostic details. */
final class OperationException extends \RuntimeException {
	/**
	 * Bind the explicitly supplied dependencies and immutable context.
	 *
	 * @param string $error_code Stable machine-readable error code.
	 * @param int    $status Status supplied explicitly by the caller.
	 * @param bool   $retryable Retryable supplied explicitly by the caller.
	 * @param array  $fields Fields supplied explicitly by the caller.
	 */
	public function __construct( public readonly string $error_code, public readonly int $status, public readonly bool $retryable = false, public readonly array $fields = array() ) {
		parent::__construct( $error_code );
	}
}
