<?php
use PHPUnit\Framework\TestCase;
use SessionFlowBookings\Domain\Version;
use SessionFlowBookings\Domain\OperationException;
final class VersionTest extends TestCase {
 public function test_strong_version(): void { self::assertSame( 12, Version::parse( '"12"' ) ); Version::assert( 12, 12 ); }
 public function test_missing_version(): void { $this->expectException( OperationException::class ); try { Version::parse( null ); } catch ( OperationException $e ) { self::assertSame( 428, $e->status ); throw $e; } }
 public function test_weak_version_rejected(): void { $this->expectException( OperationException::class ); Version::parse( 'W/"12"' ); }
 public function test_stale_version(): void { $this->expectException( OperationException::class ); try { Version::assert( 1, 2 ); } catch ( OperationException $e ) { self::assertSame( 412, $e->status ); throw $e; } }
}
