<?php
use PHPUnit\Framework\TestCase;
use SessionFlowBookings\Infrastructure\Database\TableNames;
abstract class SfbkIntegrationCase extends TestCase {
 protected string $resource_id;
 protected function setUp(): void {
  global $wpdb;
  self::assertSame('sfbk_test',DB_NAME);
  wp_set_current_user(0); wp_set_current_user(1);
  foreach (TableNames::SUFFIXES as $suffix) { $wpdb->query($wpdb->prepare('DELETE FROM %i',(new TableNames($wpdb))->get($suffix))); }
  $table=$wpdb->prefix.'sfbk_test_resources';
  $wpdb->query($wpdb->prepare('CREATE TABLE IF NOT EXISTS %i (id char(36) PRIMARY KEY, site_id bigint NOT NULL,owner_id bigint NOT NULL,version bigint NOT NULL,label varchar(100) NOT NULL) ENGINE=InnoDB',$table));
  $wpdb->query($wpdb->prepare('DELETE FROM %i',$table));
  $this->resource_id=wp_generate_uuid4();
  $wpdb->insert($table,array('id'=>$this->resource_id,'site_id'=>get_current_blog_id(),'owner_id'=>1,'version'=>1,'label'=>'initial'),array('%s','%d','%d','%d','%s'));
 }
 protected function request(string $method='PUT',string $body='{"label":"updated"}',?string $key=null,?string $version='"1"'): WP_REST_Request {
  $r=new WP_REST_Request($method,'/sfbk-test/v1/resources/'.$this->resource_id);
  $r->set_url_params(array('id'=>$this->resource_id));$r->set_body($body);
  $r->set_header('X-WP-Nonce',wp_create_nonce('wp_rest'));
  if ($version!==null) { $r->set_header('If-Match',$version); }
  $r->set_header('Idempotency-Key',$key??wp_generate_uuid4());return $r;
 }
 protected function handle(WP_REST_Request $r): WP_REST_Response { return (new SessionFlowBookingsTests\DemoController())->handle($r); }
 protected function count_rows(string $suffix): int { global $wpdb;return (int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM %i',(new TableNames($wpdb))->get($suffix))); }
}
