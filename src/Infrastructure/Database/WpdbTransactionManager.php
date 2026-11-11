<?php
/**
 * Provide the WpdbTransactionManager adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\Infrastructure\Database;

use SessionFlowBookings\Application\Ports\TransactionManager;

/** Roll back every local effect on exceptions, including commit failure. */
final class WpdbTransactionManager implements TransactionManager {
	/**
	 * Request-local adapter state.
	 *
	 * @var bool Whether a local transaction is active.
	 */
	private bool $active = false;
	/**
	 * Bind the explicitly supplied dependencies and immutable context.
	 *
	 * @param \wpdb $db Site-scoped WordPress database connection.
	 */
	public function __construct( private readonly \wpdb $db ) {}
	/**
	 * Execute the scoped operation and propagate failure without partial success.
	 *
	 * @param callable $callback Local database operation, safe to retry after rollback; never performs external effects.
	 * @return mixed Operation result according to the adapter contract.
	 * @throws \LogicException If a nested transaction is attempted.
	 * @throws \Throwable If the operation fails; local effects are rolled back.
	 * @throws \RuntimeException If the operation cannot satisfy its preconditions or persist its result.
	 */
	public function run( callable $callback ): mixed {
		if ( $this->active ) {
			throw new \LogicException( 'Nested transaction' ); }
		for ( $attempt = 0; $attempt <= 3; ++$attempt ) {
			if ( false === $this->db->query( 'START TRANSACTION' ) ) {
				throw new \RuntimeException( 'Transaction start failed' );
			}
			$this->active = true;
			try {
				$result = $callback();
				if ( false === $this->db->query( 'COMMIT' ) ) {
					throw new \RuntimeException( 'Commit failed' );
				}
				return $result;
			} catch ( \Throwable $error ) {
				// Capture the server error before ROLLBACK clears connection diagnostics.
				$connection = $this->db->dbh;
				// phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_errno -- wpdb exposes no numeric error accessor; read its existing connection diagnostic only, without issuing SQL.
				$errno = $connection instanceof \mysqli ? mysqli_errno( $connection ) : 0;
				$this->db->query( 'ROLLBACK' );
				if ( in_array( $errno, array( 1205, 1213 ), true ) && $attempt < 3 ) {
					continue;
				}
				throw $error;
			} finally {
				$this->active = false;
			}
		}
	}
}
