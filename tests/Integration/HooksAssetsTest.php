<?php
final class HooksAssetsTest extends SfbkIntegrationCase {
 public function test_unrelated_admin_has_no_assets(): void {
  require_once ABSPATH.'wp-admin/includes/screen.php';set_current_screen('dashboard');$assets=new SessionFlowBookings\WordPress\Admin\Assets();$assets->enqueue('index.php');self::assertFalse(wp_style_is('sfbk-admin','enqueued'));
  set_current_screen('toplevel_page_sfbk-dashboard');$assets->enqueue('toplevel_page_sfbk-dashboard');self::assertTrue(wp_style_is('sfbk-admin','enqueued'));wp_dequeue_style('sfbk-admin');
 }
 public function test_plugin_hooks_registered_once(): void {
  $plugin=new SessionFlowBookings\Plugin();$plugin->register();$before=has_action('admin_post_sfbk_save_uninstall');$plugin->register();self::assertSame($before,has_action('admin_post_sfbk_save_uninstall'));self::assertNotFalse($before);
 }
}
