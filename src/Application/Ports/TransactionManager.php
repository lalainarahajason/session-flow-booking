<?php
/**
 * Provide the TransactionManager adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\Application\Ports;

/** Persistence boundary used by application orchestration. */
interface TransactionManager {
	/**
	 * Execute the scoped operation and propagate failure without partial success.
	 *
	 * @param callable $callback Operation executed within the transaction.
	 * @return mixed Operation result according to the adapter contract.
	 * @throws \RuntimeException If the operation cannot satisfy its preconditions or persist its result.
	 */
	public function run( callable $callback ): mixed;
}
