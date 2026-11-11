<?php
final class AdminPermissionsTest extends SfbkIntegrationCase {
 public function test_manager_defaults_and_trainer_access(): void {
  $manager=get_role('sfbk_manager');self::assertTrue($manager->has_cap('booking_read_finance'));self::assertFalse($manager->has_cap('booking_refund'));self::assertFalse($manager->has_cap('booking_record_payments'));self::assertFalse($manager->has_cap('booking_manage_settings'));
  $id=wp_insert_user(array('user_login'=>'sfbk_test_trainer','user_pass'=>'test-only','role'=>'sfbk_trainer'));if(is_wp_error($id)){$id=get_user_by('login','sfbk_test_trainer')->ID;}
  wp_set_current_user($id);self::assertFalse(current_user_can('booking_manage_settings'));self::assertFalse(current_user_can('booking_read_finance'));self::assertTrue((new SessionFlowBookings\WordPress\Admin\Menu())->can_enter());
 }
 public function test_object_scope_and_nonce(): void {
  $request=$this->request('GET');$request->set_header('X-WP-Nonce','invalid');self::assertSame(403,$this->handle($request)->get_status());
  wp_set_current_user(0);self::assertSame(401,$this->handle($this->request('GET'))->get_status());
 }
 public function test_dynamic_title_is_escaped(): void {
  ob_start();(new SessionFlowBookings\WordPress\Admin\Menu())->render('dashboard','read','<script>alert(1)</script>');$html=ob_get_clean();self::assertStringContainsString('&lt;script&gt;',$html);self::assertStringNotContainsString('<script>',$html);
 }
}
