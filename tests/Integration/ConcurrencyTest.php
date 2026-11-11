<?php
require_once dirname(__DIR__).'/Support/ConcurrentRunner.php';
final class ConcurrencyTest extends SfbkIntegrationCase {
 public function test_two_independent_connections_same_intention_one_effect(): void {
  $results=SfbkConcurrentRunner::mutate($this->resource_id,wp_generate_uuid4());self::assertSame(array(200,200),array_column($results,'status'));self::assertSame(1,$this->count_rows('audit_events'));self::assertSame(1,$this->count_rows('domain_outbox'));self::assertSame(1,$this->count_rows('idempotency_records'));
  self::assertEqualsCanonicalizing(array('true','false'),array_map(fn($r)=>$r['headers']['Idempotency-Replayed'],$results));
 }
 public function test_two_workers_claim_distinct_jobs_concurrently(): void {
  global $wpdb;$table=(new SessionFlowBookings\Infrastructure\Database\TableNames($wpdb))->get('jobs');$ids=array();
  for($i=0;$i<2;$i++){$id=wp_generate_uuid4();$ids[]=$id;$wpdb->insert($table,array('id'=>$id,'site_id'=>get_current_blog_id(),'kind'=>'demo','principal_scope'=>'wp:1','source_scope_snapshot'=>'{}','state'=>'queued','available_at'=>gmdate('Y-m-d H:i:s')),array('%s','%d','%s','%s','%s','%s','%s'));}
  $results=SfbkConcurrentRunner::mutate($this->resource_id,wp_generate_uuid4(),'claim');self::assertNotSame($results[0]['id'],$results[1]['id']);self::assertEqualsCanonicalizing($ids,array_column($results,'id'));
 }
}
