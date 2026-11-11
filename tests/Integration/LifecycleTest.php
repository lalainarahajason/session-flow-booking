<?php
final class LifecycleTest extends SfbkIntegrationCase {
 public function test_reactivation_preserves_schema_and_single_cron(): void {
  SessionFlowBookings\WordPress\Lifecycle::activate();SessionFlowBookings\WordPress\Lifecycle::activate();
  self::assertSame('0001',get_option('sfbk_schema_version'));
  self::assertNotFalse(wp_next_scheduled('sfbk_worker_tick'));
  self::assertTrue(current_user_can('booking_manage_settings'));
  SessionFlowBookings\WordPress\Lifecycle::deactivate();self::assertFalse(wp_next_scheduled('sfbk_worker_tick'));
  global $wpdb;self::assertNotNull((new SessionFlowBookingsTests\DemoRepository($wpdb))->get($this->resource_id));
 }
}
