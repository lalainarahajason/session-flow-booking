<?php
final class IdempotencyTest extends SfbkIntegrationCase {
 public function test_replay_before_stale_version_and_mismatch(): void {
  $key=wp_generate_uuid4();$one=$this->handle($this->request('PUT','{"label":"same"}',$key));$two=$this->handle($this->request('PUT','{"label":"same"}',$key));
  self::assertSame(200,$one->get_status());self::assertSame(200,$two->get_status());self::assertSame('true',$two->get_headers()['Idempotency-Replayed']);
  self::assertSame(1,$this->count_rows('audit_events'));self::assertSame(1,$this->count_rows('domain_outbox'));
  self::assertSame(409,$this->handle($this->request('PUT','{"label":"different"}',$key))->get_status());
 }
 public function test_revoked_rights_not_replayed(): void {
  $key=wp_generate_uuid4();$this->handle($this->request('PUT','{"label":"same"}',$key));$user=wp_get_current_user();$user->add_cap('booking_manage_catalog',false);
  try {self::assertSame(403,$this->handle($this->request('PUT','{"label":"same"}',$key))->get_status());}
  finally {$user->remove_cap('booking_manage_catalog');}
 }
}
