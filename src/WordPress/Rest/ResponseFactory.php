<?php
/**
 * Provide the ResponseFactory adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\WordPress\Rest;

use SessionFlowBookings\Domain\OperationException;

/** Uniform private JSON envelopes without SQL or exception messages. */
final class ResponseFactory {
	/**
	 * Serialize an explicit private success projection and diagnostic identifier.
	 *
	 * @param array  $data Explicit authorized response projection.
	 * @param string $request_id Opaque diagnostic correlation UUID.
	 * @param array  $headers Additional response headers.
	 * @return \WP_REST_Response Operation result according to the adapter contract.
	 */
	public function success( array $data, string $request_id, array $headers = array() ): \WP_REST_Response {
		return new \WP_REST_Response(
			array(
				'data'       => $data,
				'request_id' => $request_id,
			),
			200,
			array_merge( array( 'Cache-Control' => 'no-store' ), $headers )
		);
	}
	/**
	 * Serialize a safe error code without exposing exception or database details.
	 *
	 * @param \Throwable $error Failure whose private details must not be exposed.
	 * @param string     $request_id Opaque diagnostic correlation UUID.
	 * @return \WP_REST_Response Operation result according to the adapter contract.
	 */
	public function error( \Throwable $error, string $request_id ): \WP_REST_Response {
		$expected = $error instanceof OperationException;
		$code     = $expected ? $error->error_code : 'INTERNAL_ERROR';
		$response = new \WP_REST_Response(
			array(
				'error' => array(
					'code'       => $code,
					'message'    => $code,
					'request_id' => $request_id,
					'retryable'  => $expected && $error->retryable,
					'fields'     => $expected ? $error->fields : array(),
				),
			),
			$expected ? $error->status : 500,
			array( 'Cache-Control' => 'no-store' )
		);
		if ( 'REQUEST_IN_PROGRESS' === $code ) {
			$response->header( 'Retry-After', '2' ); }
		return $response;
	}
}
