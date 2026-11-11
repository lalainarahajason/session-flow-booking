<?php
/**
 * Provide the JobStore adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\Application\Ports;

/** Persistence boundary used by application orchestration. */
interface JobStore {
	/**
	 * Acquire the next eligible job using an expiring possession token.
	 *
	 * @return array|null Contract result.
	 */
	public function claim(): ?array;
	/**
	 * Complete only the job still owned by this unexpired token.
	 *
	 * @param string $id Opaque internal identifier.
	 * @param string $token Current durable lease possession token.
	 * @throws \RuntimeException If the operation cannot satisfy its preconditions or persist its result.
	 */
	public function acknowledge( string $id, string $token ): void;
}
