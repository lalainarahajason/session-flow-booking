<?php
final class MigrationTest extends SfbkIntegrationCase {
 public function test_repeat_and_resume_preserve_data(): void {
  global $wpdb;$this->handle($this->request());
  $wpdb->query($wpdb->prepare('DROP TABLE %i',(new SessionFlowBookings\Infrastructure\Database\TableNames($wpdb))->get('jobs')));
  update_option('sfbk_migration_checkpoint',3,false);delete_option('sfbk_schema_version');
  (new SessionFlowBookings\Infrastructure\Database\MigrationRunner($wpdb))->run();
  self::assertSame(1,$this->count_rows('audit_events'));self::assertFalse(get_option('sfbk_migration_checkpoint'));self::assertSame('0001',get_option('sfbk_schema_version'));
 }
 public function test_unknown_schema_blocks_without_downgrade(): void {
  global $wpdb;update_option('sfbk_schema_version','9999',false);
  try {(new SessionFlowBookings\Infrastructure\Database\MigrationRunner($wpdb))->run();self::fail('Expected migration refusal');}
  catch(RuntimeException $e){self::assertSame('9999',get_option('sfbk_schema_version'));self::assertSame('MIGRATION_FAILED',get_option('sfbk_schema_error'));}
  finally{update_option('sfbk_schema_version','0001',false);delete_option('sfbk_schema_error');}
 }
}
