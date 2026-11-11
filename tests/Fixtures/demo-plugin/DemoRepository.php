<?php
namespace SessionFlowBookingsTests;

/** Fixture storage is installed only by the integration bootstrap. */
final class DemoRepository {
 public function __construct( private readonly \wpdb $db ) {}
 public function get( string $id, bool $lock = false ): ?array {
  $table = $this->db->prefix . 'sfbk_test_resources';
  $sql = $lock ? 'SELECT * FROM %i WHERE id=%s AND site_id=%d FOR UPDATE' : 'SELECT * FROM %i WHERE id=%s AND site_id=%d';
  return $this->db->get_row( $this->db->prepare( $sql, $table, $id, get_current_blog_id() ), ARRAY_A );
 }
 public function update( string $id, int $version, string $label ): void {
  if ( 1 !== $this->db->update( $this->db->prefix . 'sfbk_test_resources', array( 'version' => $version + 1, 'label' => $label ), array( 'id' => $id, 'site_id' => get_current_blog_id(), 'version' => $version ), array( '%d', '%s' ), array( '%s', '%d', '%d' ) ) ) { throw new \RuntimeException( 'Mutation failed' ); }
 }
}
