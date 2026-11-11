<?php
/**
 * Provide the RequestContext adapter for the booking infrastructure.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\WordPress\Rest;

use SessionFlowBookings\Domain\Principal;
use SessionFlowBookings\Domain\OperationException;
use SessionFlowBookings\WordPress\Capabilities;

/** Explicit cookie principal without fallback to a weaker identity. */
final class RequestContext {
	/**
	 * Establish the authenticated cookie principal with a valid REST nonce.
	 *
	 * @param \WP_REST_Request $request WordPress REST request.
	 * @return Principal Operation result according to the adapter contract.
	 * @throws OperationException If the operation cannot satisfy its preconditions or persist its result.
	 */
	public function principal( \WP_REST_Request $request ): Principal {
		if ( ! is_user_logged_in() ) {
			throw new OperationException( 'AUTH_REQUIRED', 401 ); }
		if ( ! wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ) ?? '', 'wp_rest' ) ) {
			throw new OperationException( 'CSRF_INVALID', 403 ); }
		$capabilities = array_values( array_filter( Capabilities::ALL, static fn( $capability ) => current_user_can( $capability ) ) );
		return new Principal( get_current_blog_id(), get_current_user_id(), $capabilities );
	}
	/**
	 * Decode bounded JSON without accepting malformed documents.
	 *
	 * @param \WP_REST_Request $request WordPress REST request.
	 * @return mixed Operation result according to the adapter contract.
	 * @throws OperationException If the operation cannot satisfy its preconditions or persist its result.
	 */
	public function body( \WP_REST_Request $request ): mixed {
		$body = $request->get_body();
		if ( strlen( $body ) > 262144 ) {
			throw new OperationException( 'BODY_TOO_LARGE', 413 ); }
		try {
			return json_decode( $body, false, 512, JSON_THROW_ON_ERROR );
		} catch ( \JsonException $error ) {
			throw new OperationException( 'INVALID_JSON', 400 ); }
	}
}
