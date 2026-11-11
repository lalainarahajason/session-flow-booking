<?php
final class TransactionOutboxTest extends SfbkIntegrationCase {
 public function test_rollback_removes_mutation_audit_outbox_and_intention(): void {
  global $wpdb;$tx=new SessionFlowBookings\Infrastructure\Database\WpdbTransactionManager($wpdb);
  try {$tx->run(function()use($wpdb){(new SessionFlowBookingsTests\DemoRepository($wpdb))->update($this->resource_id,1,'rolled back');(new SessionFlowBookings\Infrastructure\Database\WpdbOutboxStore($wpdb))->append(new SessionFlowBookings\Domain\Event(wp_generate_uuid4(),$this->resource_id,2,'demo.updated',array()),get_current_blog_id());throw new RuntimeException('Injected before commit');});self::fail('Expected rollback');}
  catch(RuntimeException $e){self::assertSame('Injected before commit',$e->getMessage());}
  self::assertSame(0,$this->count_rows('domain_outbox'));self::assertSame('initial',(new SessionFlowBookingsTests\DemoRepository($wpdb))->get($this->resource_id)['label']);
 }
 public function test_failure_after_all_writes_rolls_back_the_entire_intention(): void {
  global $wpdb;$principal=new SessionFlowBookings\Domain\Principal(get_current_blog_id(),1,array('booking_manage_catalog','booking_manage_settings'));
  $mutator=new SessionFlowBookings\Application\IdempotentMutation(new SessionFlowBookings\Infrastructure\Database\WpdbTransactionManager($wpdb),new SessionFlowBookings\Infrastructure\Database\WpdbIdempotencyStore($wpdb));
  try{$mutator->run($principal,'demo.rollback',wp_generate_uuid4(),str_repeat('a',64),function(){},function()use($wpdb){
   (new SessionFlowBookingsTests\DemoRepository($wpdb))->update($this->resource_id,1,'partial');
   (new SessionFlowBookings\Infrastructure\Database\WpdbAuditStore($wpdb))->append(array('site_id'=>get_current_blog_id(),'request_id'=>wp_generate_uuid4(),'operation_id'=>'demo.rollback','actor_type'=>'wp','actor_id'=>1,'action'=>'demo.rollback','subject_id'=>$this->resource_id,'version_before'=>1,'version_after'=>2));
   (new SessionFlowBookings\Infrastructure\Database\WpdbOutboxStore($wpdb))->append(new SessionFlowBookings\Domain\Event(wp_generate_uuid4(),$this->resource_id,2,'demo.updated',array()),get_current_blog_id());throw new RuntimeException('Injected after all writes');
  });self::fail('Expected rollback');}catch(RuntimeException $e){self::assertSame('Injected after all writes',$e->getMessage());}
  foreach(array('audit_events','domain_outbox','idempotency_records') as $suffix){self::assertSame(0,$this->count_rows($suffix));}self::assertSame('initial',(new SessionFlowBookingsTests\DemoRepository($wpdb))->get($this->resource_id)['label']);
 }
}
