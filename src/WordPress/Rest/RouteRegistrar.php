<?php
/**
 * Register isolated REST adapters with consistent error serialization.
 *
 * @package SessionFlowBookings
 */

// phpcs:disable WordPress.Files.FileName -- Approved Composer PSR-4 paths match class names.
namespace SessionFlowBookings\WordPress\Rest;

use SessionFlowBookings\Domain\OperationException;

/** Explicit namespaces constrain normalization to this plugin's adapters. */
final class RouteRegistrar {
	/**
	 * Registered namespaces for this request.
	 *
	 * @var array<string,bool>
	 */
	private static array $namespaces = array();

	/**
	 * Register the adapter and its permission policy with one normalizer.
	 *
	 * @param string $api_namespace Explicit namespace.
	 * @param string $route Adapter path pattern.
	 * @param array  $endpoints Callback and permission definitions.
	 */
	public function register( string $api_namespace, string $route, array $endpoints ): void {
		if ( ! self::$namespaces ) {
			add_filter( 'rest_post_dispatch', array( self::class, 'normalize' ), 10, 3 );
		}
		self::$namespaces[ $api_namespace ] = true;
		register_rest_route( $api_namespace, $route, $endpoints );
	}

	/**
	 * Normalize core authentication and transport errors on registered routes.
	 *
	 * @param mixed            $response Core REST response.
	 * @param \WP_REST_Server  $server Current server instance.
	 * @param \WP_REST_Request $request Current request.
	 * @return mixed Unrelated responses are preserved.
	 */
	public static function normalize( mixed $response, \WP_REST_Server $server, \WP_REST_Request $request ): mixed {
		$namespace = explode( '/', ltrim( $request->get_route(), '/' ) );
		$prefix    = implode( '/', array_slice( $namespace, 0, 2 ) );
		if ( ! isset( self::$namespaces[ $prefix ] ) || ! $server ) {
			return $response;
		}
		$response = rest_ensure_response( $response );
		if ( is_wp_error( $response ) ) {
			$response = $server->error_to_response( $response );
		}
		$data = $response->get_data();
		if ( $response->get_status() < 400 || isset( $data['error'] ) ) {
			return $response;
		}
		$status = $response->get_status();
		$codes  = array(
			400 => 'INVALID_JSON',
			401 => 'AUTH_REQUIRED',
			403 => 'FORBIDDEN',
			404 => 'NOT_FOUND',
			422 => 'VALIDATION_ERROR',
		);
		$code   = $codes[ $status ] ?? 'INTERNAL_ERROR';
		if ( 'rest_cookie_invalid_nonce' === ( $data['code'] ?? '' ) || 'CSRF_INVALID' === ( $data['code'] ?? '' ) ) {
			$code = 'CSRF_INVALID';
		}
		return ( new ResponseFactory() )->error( new OperationException( $code, $status ), wp_generate_uuid4() );
	}
}
