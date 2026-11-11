<?php
/**
 * Provide the IdempotentMutation adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\Application;

use SessionFlowBookings\Application\Ports\TransactionManager;
use SessionFlowBookings\Application\Ports\IdempotencyStore;
use SessionFlowBookings\Domain\Principal;
use SessionFlowBookings\Domain\OperationException;

/** Authorized replay precedes optimistic version comparison in the callback. */
final class IdempotentMutation {
	/**
	 * Bind the explicitly supplied dependencies and immutable context.
	 *
	 * @param TransactionManager $transactions Transaction boundary for local effects.
	 * @param IdempotencyStore   $store Store supplied explicitly by the caller.
	 */
	public function __construct( private readonly TransactionManager $transactions, private readonly IdempotencyStore $store ) {}
	/**
	 * Hash the canonical intention including the original version precondition.
	 *
	 * @param string $method Canonical HTTP method.
	 * @param string $path Canonical request path or JSON Pointer.
	 * @param mixed  $dto Validated closed request object.
	 * @param string $if_match Original strong version header.
	 * @return string Operation result according to the adapter contract.
	 */
	public static function fingerprint( string $method, string $path, mixed $dto, string $if_match ): string {
		$canonical = static function ( mixed $value ) use ( &$canonical ): mixed {
			if ( $value instanceof \stdClass ) {
				$items = get_object_vars( $value );
				ksort( $items, SORT_STRING );
				foreach ( $items as &$item ) {
							$item = $canonical( $item );
				} return (object) $items; }
			if ( is_array( $value ) ) {
				return array_map( $canonical, $value ); }
			return $value;
		};
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Deterministic strict canonical encoding must throw rather than repair invalid input.
		return hash( 'sha256', json_encode( array( $method, $path, $canonical( $dto ), $if_match ), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
	}
	/**
	 * Execute the scoped operation and propagate failure without partial success.
	 *
	 * @param Principal   $principal Verified server-side identity and capabilities.
	 * @param string      $operation Stable operation identifier.
	 * @param string|null $key UUID intention key.
	 * @param string      $fingerprint Canonical intention digest.
	 * @param callable    $authorize Authorization rechecked before result restitution.
	 * @param callable    $mutate Mutation executed only for a new authorized intention.
	 * @return array Operation result according to the adapter contract.
	 * @throws OperationException If the operation cannot satisfy its preconditions or persist its result.
	 */
	public function run( Principal $principal, string $operation, ?string $key, string $fingerprint, callable $authorize, callable $mutate ): array {
		if ( ! $key ) {
			throw new OperationException( 'PRECONDITION_REQUIRED', 428 ); }
		if ( 1 !== preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/iD', $key ) ) {
			throw new OperationException( 'VALIDATION_ERROR', 422 ); }
		$authorize();
		return $this->transactions->run(
			function () use ( $principal, $operation, $key, $fingerprint, $authorize, $mutate ) {
				$authorize();
				$result = $this->store->reserve( $principal, $operation, $key, $fingerprint );
				if ( null !== $result ) {
					return array(
						'data'     => $result,
						'replayed' => true,
					); }
				$result = $mutate();
				$this->store->complete( $key, $result );
				return array(
					'data'     => $result,
					'replayed' => false,
				);
			}
		);
	}
}
