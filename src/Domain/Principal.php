<?php
/**
 * Provide the Principal adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\Domain;

/** Server-established identity; never constructed from a request DTO. */
final class Principal {
	/**
	 * Bind the explicitly supplied dependencies and immutable context.
	 *
	 * @param int   $site_id Server-established site identifier.
	 * @param int   $user_id User id supplied explicitly by the caller.
	 * @param array $capabilities Capabilities supplied explicitly by the caller.
	 */
	public function __construct( public readonly int $site_id, public readonly int $user_id, public readonly array $capabilities = array() ) {}
	/**
	 * Return the server-established WordPress identity scope.
	 *
	 * @return string Contract result.
	 */
	public function scope(): string {
		return 'wp:' . $this->user_id; }
}
