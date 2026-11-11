<?php
final class RestContractTest extends SfbkIntegrationCase {
 public function test_success_and_private_headers(): void {$r=$this->handle($this->request('GET'));self::assertSame(200,$r->get_status());self::assertSame('no-store',$r->get_headers()['Cache-Control']);self::assertSame('"1"',$r->get_headers()['ETag']);}
 public function test_strict_dto_and_no_effect(): void {foreach(array('{"label":"ok","status":"paid"}','{"label":1}','{"label":"ok","nested":{}}') as $body){$r=$this->handle($this->request('PUT',$body));self::assertSame(422,$r->get_status());}self::assertSame(0,$this->count_rows('audit_events'));}
 public function test_preconditions(): void {self::assertSame(428,$this->handle($this->request('PUT','{"label":"ok"}',null,null))->get_status());self::assertSame(412,$this->handle($this->request('PUT','{"label":"ok"}',null,'"2"'))->get_status());self::assertSame(0,$this->count_rows('audit_events'));}
 public function test_bad_json_and_size(): void {self::assertSame(400,$this->handle($this->request('PUT','{'))->get_status());self::assertSame(413,$this->handle($this->request('PUT',str_repeat('a',262145)))->get_status());}
 public function test_real_dispatch_normalizes_permission_errors(): void {
  wp_set_current_user(0);
  $server=rest_get_server();(new SessionFlowBookingsTests\DemoController())->register();
  $request=$this->request('GET');$response=$server->dispatch($request);
  $response=apply_filters('rest_post_dispatch',$response,$server,$request);
  self::assertSame(401,$response->get_status());self::assertSame('AUTH_REQUIRED',$response->get_data()['error']['code']);self::assertSame('no-store',$response->get_headers()['Cache-Control']);
 }
 public function test_sql_metacharacters_are_persisted_as_values(): void {
  global $wpdb;$label="x'); DROP TABLE sfbk_jobs; --";$response=$this->handle($this->request('PUT',wp_json_encode(array('label'=>$label))));
  self::assertSame(200,$response->get_status());self::assertSame($label,(new SessionFlowBookingsTests\DemoRepository($wpdb))->get($this->resource_id)['label']);self::assertSame(1,$this->count_rows('audit_events'));
 }
}
