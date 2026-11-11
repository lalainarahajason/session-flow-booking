<?php
final class WorkerRecoveryTest extends SfbkIntegrationCase {
 private function job(): string {
  global $wpdb;$id=wp_generate_uuid4();$wpdb->insert((new SessionFlowBookings\Infrastructure\Database\TableNames($wpdb))->get('jobs'),array('id'=>$id,'site_id'=>get_current_blog_id(),'kind'=>'demo','principal_scope'=>'wp:1','source_scope_snapshot'=>'{}','state'=>'queued','available_at'=>gmdate('Y-m-d H:i:s'),'total_items'=>1),array('%s','%d','%s','%s','%s','%s','%s','%d'));return $id;
 }
 public function test_expired_lease_is_reclaimed_and_old_worker_rejected(): void {
  global $wpdb;$id=$this->job();$store=new SessionFlowBookings\Infrastructure\Database\WpdbJobStore($wpdb);$old=$store->claim();
  $wpdb->update((new SessionFlowBookings\Infrastructure\Database\TableNames($wpdb))->get('jobs'),array('lease_until'=>'2000-01-01 00:00:00'),array('id'=>$id),array('%s'),array('%s'));$new=$store->claim();self::assertNotSame($old['lease_token'],$new['lease_token']);
  try {$store->acknowledge($id,$old['lease_token']);self::fail('Old worker must not acknowledge');}catch(RuntimeException $e){self::assertSame('Lease lost',$e->getMessage());}
  $store->acknowledge($id,$new['lease_token']);self::assertNull($store->claim());
 }
 public function test_effect_and_ack_rollback_then_retry_once(): void {
  global $wpdb;$id=$this->job();$tx=new SessionFlowBookings\Infrastructure\Database\WpdbTransactionManager($wpdb);$store=new SessionFlowBookings\Infrastructure\Database\WpdbJobStore($wpdb);
  $handler=function()use($wpdb){(new SessionFlowBookingsTests\DemoRepository($wpdb))->update($this->resource_id,1,'effect');throw new RuntimeException('Crash before ack');};
  (new SessionFlowBookings\Application\JobWorker($tx,$store,array('demo'=>$handler)))->tick(1);
  self::assertSame('initial',(new SessionFlowBookingsTests\DemoRepository($wpdb))->get($this->resource_id)['label']);
  $table=(new SessionFlowBookings\Infrastructure\Database\TableNames($wpdb))->get('jobs');$wpdb->update($table,array('available_at'=>'2000-01-01 00:00:00'),array('id'=>$id),array('%s'),array('%s'));
  (new SessionFlowBookings\Application\JobWorker($tx,$store,array('demo'=>function()use($wpdb){(new SessionFlowBookingsTests\DemoRepository($wpdb))->update($this->resource_id,1,'effect');})))->tick(1);
  self::assertSame('effect',(new SessionFlowBookingsTests\DemoRepository($wpdb))->get($this->resource_id)['label']);self::assertNull($store->claim());
  self::assertSame('2',$wpdb->get_var($wpdb->prepare('SELECT version FROM %i WHERE id=%s',$wpdb->prefix.'sfbk_test_resources',$this->resource_id)));
 }
 public function test_outbox_consumer_duplicate_jobs_produce_one_effect(): void {
  global $wpdb;require_once dirname(__DIR__).'/Fixtures/demo-plugin/DemoJobHandler.php';
  self::assertSame(200,$this->handle($this->request())->get_status());
  $names=new SessionFlowBookings\Infrastructure\Database\TableNames($wpdb);$event=$wpdb->get_var($wpdb->prepare('SELECT event_id FROM %i',$names->get('domain_outbox')));
  for($i=0;$i<2;$i++){$id=$this->job();$wpdb->update($names->get('jobs'),array('source_scope_snapshot'=>wp_json_encode(array('event_id'=>$event))),array('id'=>$id),array('%s'),array('%s'));}
  $worker=new SessionFlowBookings\Application\JobWorker(new SessionFlowBookings\Infrastructure\Database\WpdbTransactionManager($wpdb),new SessionFlowBookings\Infrastructure\Database\WpdbJobStore($wpdb),array('demo'=>new SessionFlowBookingsTests\DemoJobHandler($wpdb)));$worker->tick();
  $resource=(new SessionFlowBookingsTests\DemoRepository($wpdb))->get($this->resource_id);self::assertSame('3',$resource['version']);self::assertSame('consumed',$resource['label']);
  self::assertSame('2',$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM %i WHERE state='succeeded'",$names->get('jobs'))));
 }
}
