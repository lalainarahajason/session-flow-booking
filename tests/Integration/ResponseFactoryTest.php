<?php
final class ResponseFactoryTest extends SfbkIntegrationCase {
 public function test_internal_exception_details_do_not_escape_to_response(): void {
  $response=(new SessionFlowBookings\WordPress\Rest\ResponseFactory())->error(new RuntimeException('private SQL credentials'),wp_generate_uuid4());
  self::assertSame(500,$response->get_status());self::assertSame('INTERNAL_ERROR',$response->get_data()['error']['code']);self::assertStringNotContainsString('credentials',wp_json_encode($response->get_data()));self::assertSame('no-store',$response->get_headers()['Cache-Control']);
 }
 public function test_validation_pointer_is_json_data_without_html_corruption(): void {
  $error=new SessionFlowBookings\Domain\OperationException('VALIDATION_ERROR',422,false,array(array('path'=>'/<script>','code'=>'INVALID_VALUE')));
  $response=(new SessionFlowBookings\WordPress\Rest\ResponseFactory())->error($error,wp_generate_uuid4());
  $encoded=wp_json_encode($response->get_data());self::assertSame('/<script>',json_decode($encoded,true)['error']['fields'][0]['path']);self::assertSame(422,$response->get_status());
 }
}
