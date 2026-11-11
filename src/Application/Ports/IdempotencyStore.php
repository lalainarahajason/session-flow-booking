<?php
/**
 * Provide the IdempotencyStore adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\Application\Ports;

/** Persistence boundary used by application orchestration. */
interface IdempotencyStore {
	/**
	 * Serialize an intention and return its authorized completed result if present.
	 *
	 * @param \SessionFlowBookings\Domain\Principal $principal Verified server-side identity and capabilities.
	 * @param string                                $operation Stable operation identifier.
	 * @param string                                $key UUID intention key.
	 * @param string                                $fingerprint Canonical intention digest.
	 * @return array|null Operation result according to the adapter contract.
	 * @throws \RuntimeException If the operation cannot satisfy its preconditions or persist its result.
	 */
	public function reserve( \SessionFlowBookings\Domain\Principal $principal, string $operation, string $key, string $fingerprint ): ?array;
	/**
	 * Persist the result of the currently reserved intention.
	 *
	 * @param string $key UUID intention key.
	 * @param array  $result Explicit result projection to retain for authorized replay.
	 * @throws \RuntimeException If the operation cannot satisfy its preconditions or persist its result.
	 */
	public function complete( string $key, array $result ): void;
}
