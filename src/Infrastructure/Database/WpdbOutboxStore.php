<?php
/**
 * Provide the WpdbOutboxStore adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\Infrastructure\Database;

use SessionFlowBookings\Application\Ports\OutboxStore;
use SessionFlowBookings\Domain\Event;

/** Durable minimal events written in the aggregate transaction. */
final class WpdbOutboxStore implements OutboxStore {
	/**
	 * Bind the explicitly supplied dependencies and immutable context.
	 *
	 * @param \wpdb $db Site-scoped WordPress database connection.
	 */
	public function __construct( private readonly \wpdb $db ) {}
	/**
	 * Persist the minimal record in the caller transaction or throw on failure.
	 *
	 * @param Event $event Minimal immutable domain event.
	 * @param int   $site_id Server-established site identifier.
	 * @throws \RuntimeException If the operation cannot satisfy its preconditions or persist its result.
	 */
	public function append( Event $event, int $site_id ): void {
		$row = array(
			'id'                  => $event->id,
			'event_id'            => $event->id,
			'site_id'             => $site_id,
			'aggregate_type'      => 'resource',
			'aggregate_id'        => $event->aggregate_id,
			'aggregate_version'   => $event->version,
			'event_type'          => $event->type,
			'event_discriminator' => '',
			'schema_version'      => 1,
			'payload_minimal'     => wp_json_encode( $event->payload ),
			'created_at'          => gmdate( 'Y-m-d H:i:s' ),
		);
		if ( false === $this->db->insert( ( new TableNames( $this->db ) )->get( 'domain_outbox' ), $row, array( '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s' ) ) ) {
			throw new \RuntimeException( 'Outbox failed' ); }
	}
}
