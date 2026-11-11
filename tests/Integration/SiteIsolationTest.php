<?php
final class SiteIsolationTest extends SfbkIntegrationCase {
 public function test_cross_site_object_is_indistinguishable_from_missing(): void {
  global $wpdb;$wpdb->update($wpdb->prefix.'sfbk_test_resources',array('site_id'=>999),array('id'=>$this->resource_id),array('%d'),array('%s'));
  $response=$this->handle($this->request('GET'));self::assertSame(404,$response->get_status());self::assertSame('NOT_FOUND',$response->get_data()['error']['code']);
 }
 public function test_cross_owner_denied_without_global_capability(): void {
  global $wpdb;$id=username_exists('sfbk_manager_test')?:wp_insert_user(array('user_login'=>'sfbk_manager_test','user_pass'=>'test-only','role'=>'sfbk_manager'));wp_set_current_user($id);
  self::assertSame(404,$this->handle($this->request('GET'))->get_status());self::assertSame(0,$this->count_rows('audit_events'));
 }
}
