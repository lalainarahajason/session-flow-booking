<?php
/**
 * Provide the OutboxStore adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\Application\Ports;

/** Persistence boundary used by application orchestration. */
interface OutboxStore {
	/**
	 * Persist the minimal record in the caller transaction or throw on failure.
	 *
	 * @param \SessionFlowBookings\Domain\Event $event Minimal immutable domain event.
	 * @param int                               $site_id Server-established site identifier.
	 * @throws \RuntimeException If the operation cannot satisfy its preconditions or persist its result.
	 */
	public function append( \SessionFlowBookings\Domain\Event $event, int $site_id ): void;
}
