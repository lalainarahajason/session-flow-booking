<?php
namespace SessionFlowBookingsTests;
use SessionFlowBookings\WordPress\Rest\RequestContext;
use SessionFlowBookings\WordPress\Rest\ResponseFactory;
use SessionFlowBookings\WordPress\Rest\DtoValidator;
use SessionFlowBookings\Application\AuthorizationPolicy;
use SessionFlowBookings\Application\IdempotentMutation;
use SessionFlowBookings\Infrastructure\Database\WpdbTransactionManager;
use SessionFlowBookings\Infrastructure\Database\WpdbIdempotencyStore;
use SessionFlowBookings\Infrastructure\Database\WpdbAuditStore;
use SessionFlowBookings\Infrastructure\Database\WpdbOutboxStore;
use SessionFlowBookings\Domain\OperationException;
use SessionFlowBookings\Domain\Event;
use SessionFlowBookings\Domain\Version;

/** Real adapters exercised on a fixture aggregate, absent from product package. */
final class DemoController {
 public function register(): void {
  ( new \SessionFlowBookings\WordPress\Rest\RouteRegistrar() )->register( 'sfbk-test/v1', '/resources/(?P<id>[a-f0-9-]{36})', array( 'methods' => 'GET,PUT', 'callback' => array( $this, 'handle' ), 'permission_callback' => array( $this, 'permission' ) ) );
 }
 public function permission( \WP_REST_Request $request ): bool|\WP_Error {
  try { $principal = ( new RequestContext() )->principal( $request ); ( new AuthorizationPolicy() )->assert_capability( $principal, 'booking_manage_catalog' ); return true;
  } catch ( OperationException $error ) { return new \WP_Error( $error->error_code, $error->error_code, array( 'status' => $error->status ) ); }
 }
 public function handle( \WP_REST_Request $request ): \WP_REST_Response {
  global $wpdb;
  $responses = new ResponseFactory(); $request_id = wp_generate_uuid4();
  try {
   $context = new RequestContext(); $principal = $context->principal( $request ); $policy = new AuthorizationPolicy();
   $policy->assert_capability( $principal, 'booking_manage_catalog' );
   $repository = new DemoRepository( $wpdb );
   $authorize = function () use ( $principal, $policy, $repository, $request ) {
    $object = $repository->get( $request['id'] );
    if ( ! $object ) { throw new OperationException( 'NOT_FOUND', 404 ); }
    $policy->assert_visible( $principal, (int) $object['site_id'], (int) $object['owner_id'] );
   };
   $authorize();
   if ( 'GET' === $request->get_method() ) {
    $object = $repository->get( $request['id'] );
    return $responses->success( array( 'id' => $object['id'], 'version' => (int) $object['version'], 'label' => $object['label'] ), $request_id, array( 'ETag' => '"' . $object['version'] . '"' ) );
   }
   $dto = $context->body( $request );
   ( new DtoValidator() )->validate( $dto, array( 'type' => 'object', 'required' => array( 'label' ), 'properties' => array( 'label' => array( 'type' => 'string', 'minLength' => 1, 'maxLength' => 100 ) ) ) );
   $version = Version::parse( $request->get_header( 'If-Match' ) );
   $fingerprint = IdempotentMutation::fingerprint( 'PUT', $request->get_route(), $dto, $request->get_header( 'If-Match' ) );
   $mutator = new IdempotentMutation( new WpdbTransactionManager( $wpdb ), new WpdbIdempotencyStore( $wpdb ) );
   $result = $mutator->run( $principal, 'demo.replace', $request->get_header( 'Idempotency-Key' ), $fingerprint, $authorize, function () use ( $repository, $request, $version, $dto, $wpdb, $principal, $request_id, $policy ) {
    $object = $repository->get( $request['id'], true );
    if ( ! $object ) { throw new OperationException( 'NOT_FOUND', 404 ); }
    $policy->assert_visible( $principal, (int) $object['site_id'], (int) $object['owner_id'] );
    Version::assert( $version, (int) $object['version'] );
    $repository->update( $object['id'], $version, $dto->label );
    ( new WpdbAuditStore( $wpdb ) )->append( array( 'site_id' => $principal->site_id, 'request_id' => $request_id, 'operation_id' => 'demo.replace', 'actor_type' => 'wp', 'actor_id' => $principal->user_id, 'action' => 'demo.replace', 'subject_id' => $object['id'], 'version_before' => $version, 'version_after' => $version + 1 ) );
    ( new WpdbOutboxStore( $wpdb ) )->append( new Event( wp_generate_uuid4(), $object['id'], $version + 1, 'demo.updated', array( 'subject_id' => $object['id'], 'revision' => $version + 1 ) ), $principal->site_id );
    return array( 'id' => $object['id'], 'version' => $version + 1, 'label' => $dto->label );
   } );
   return $responses->success( $result['data'], $request_id, array( 'ETag' => '"' . $result['data']['version'] . '"', 'Idempotency-Replayed' => $result['replayed'] ? 'true' : 'false' ) );
  } catch ( \Throwable $error ) { return $responses->error( $error, $request_id ); }
 }
}
