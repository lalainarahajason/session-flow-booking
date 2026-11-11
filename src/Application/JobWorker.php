<?php
/**
 * Provide the JobWorker adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\Application;

use SessionFlowBookings\Infrastructure\Database\WpdbJobStore;
use SessionFlowBookings\Application\Ports\TransactionManager;

/** Bounded local effects and acknowledgement share a transaction. */
final class JobWorker {
	/**
	 * Bind the explicitly supplied dependencies and immutable context.
	 *
	 * @param TransactionManager $transactions Transaction boundary for local effects.
	 * @param WpdbJobStore       $jobs Jobs supplied explicitly by the caller.
	 * @param array              $handlers Handlers supplied explicitly by the caller.
	 */
	public function __construct( private readonly TransactionManager $transactions, private readonly WpdbJobStore $jobs, private readonly array $handlers = array() ) {}
	/**
	 * Process a bounded number of durable internal jobs.
	 *
	 * @param int $limit Maximum jobs processed by this invocation.
	 */
	public function tick( int $limit = 10 ): void {
		for ( $i = 0; $i < $limit; ++$i ) {
			$job = $this->jobs->claim();
			if ( null === $job ) {
				return; }
			try {
				$this->transactions->run(
					function () use ( $job ) {
						$this->jobs->assert_owned( $job['id'], $job['lease_token'] );
						if ( ! isset( $this->handlers[ $job['kind'] ] ) ) {
							throw new \RuntimeException( 'Unknown job handler' ); }
						( $this->handlers[ $job['kind'] ] )( $job );
						$this->jobs->acknowledge( $job['id'], $job['lease_token'] );
					}
				);
			} catch ( \Throwable $error ) {
				$this->jobs->retry( $job['id'], $job['lease_token'], (int) $job['attempt_count'] ); }
		}
	}
}
