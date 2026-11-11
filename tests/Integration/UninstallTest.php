<?php
final class UninstallTest extends SfbkIntegrationCase {
 public function test_direct_uninstaller_call_without_constant_preserves_data(): void { $this->handle($this->request());SessionFlowBookings\WordPress\Uninstaller::run();self::assertSame(1,$this->count_rows('audit_events')); }
 public function test_retention_then_explicit_owned_cleanup(): void {
  global $wpdb;if(!defined('WP_UNINSTALL_PLUGIN')){define('WP_UNINSTALL_PLUGIN','session-flow-bookings/session-flow-bookings.php');}
  $this->handle($this->request());delete_option('sfbk_delete_data_on_uninstall');SessionFlowBookings\WordPress\Uninstaller::run();self::assertSame(1,$this->count_rows('audit_events'));self::assertSame('0001',get_option('sfbk_schema_version'));
  $users=(int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM %i',$wpdb->users));update_option('sfbk_delete_data_on_uninstall',true,false);SessionFlowBookings\WordPress\Uninstaller::run();
  self::assertFalse(get_option('sfbk_schema_version'));self::assertSame($users,(int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM %i',$wpdb->users)));self::assertNotNull((new SessionFlowBookingsTests\DemoRepository($wpdb))->get($this->resource_id));
  SessionFlowBookings\WordPress\Lifecycle::activate();
 }
}
