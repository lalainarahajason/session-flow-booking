<?php
use PHPUnit\Framework\TestCase;
use SessionFlowBookings\Application\IdempotentMutation;
final class IdempotencyFingerprintTest extends TestCase {
 public function test_canonical_object_key_order(): void { self::assertSame(IdempotentMutation::fingerprint('PUT','/a',json_decode('{"z":1,"a":{"b":2,"a":3}}'),'"1"'),IdempotentMutation::fingerprint('PUT','/a',json_decode('{"a":{"a":3,"b":2},"z":1}'),'"1"')); }
 public function test_version_and_array_order_are_part_of_intention(): void { self::assertNotSame(IdempotentMutation::fingerprint('PUT','/a',array(1,2),'"1"'),IdempotentMutation::fingerprint('PUT','/a',array(2,1),'"1"')); self::assertNotSame(IdempotentMutation::fingerprint('PUT','/a',(object)array(),'"1"'),IdempotentMutation::fingerprint('PUT','/a',(object)array(),'"2"')); }
}
