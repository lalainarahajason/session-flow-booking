<?php
use PHPUnit\Framework\TestCase;
use SessionFlowBookings\Infrastructure\Logging\RedactingLogger;
final class RedactingLoggerTest extends TestCase {
 public function test_sensitive_fields_are_discarded(): void { self::assertSame(array('request_id'=>'id'),(new RedactingLogger())->context(array('request_id'=>'id','cookie'=>'private','email'=>'person@example.test','payload'=>array('token'=>'private')))); }
}
