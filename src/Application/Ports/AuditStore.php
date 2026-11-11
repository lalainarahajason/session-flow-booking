<?php
/**
 * Provide the AuditStore adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\Application\Ports;

/** Persistence boundary used by application orchestration. */
interface AuditStore {
	/**
	 * Persist the minimal record in the caller transaction or throw on failure.
	 *
	 * @param array $record Allowlisted audit fields without secrets.
	 * @throws \RuntimeException If the operation cannot satisfy its preconditions or persist its result.
	 */
	public function append( array $record ): void;
}
