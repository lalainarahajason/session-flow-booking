<?php
/**
 * Provide the Event adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\Domain;

/** Minimal durable event without copied personal information. */
final class Event {
	/**
	 * Bind the explicitly supplied dependencies and immutable context.
	 *
	 * @param string $id Opaque internal identifier.
	 * @param string $aggregate_id Aggregate id supplied explicitly by the caller.
	 * @param int    $version Version supplied explicitly by the caller.
	 * @param string $type Type supplied explicitly by the caller.
	 * @param array  $payload Payload supplied explicitly by the caller.
	 */
	public function __construct( public readonly string $id, public readonly string $aggregate_id, public readonly int $version, public readonly string $type, public readonly array $payload ) {}
}
