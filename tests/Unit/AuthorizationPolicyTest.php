<?php
use PHPUnit\Framework\TestCase;
use SessionFlowBookings\Application\AuthorizationPolicy;
use SessionFlowBookings\Domain\Principal;
use SessionFlowBookings\Domain\OperationException;
final class AuthorizationPolicyTest extends TestCase {
 public function test_owner(): void { (new AuthorizationPolicy())->assert_visible(new Principal(1,10),1,10); $this->addToAssertionCount(1); }
 public function test_other_owner(): void { $this->expectException(OperationException::class); (new AuthorizationPolicy())->assert_visible(new Principal(1,10),1,11); }
 public function test_admin_cannot_cross_site(): void { $this->expectException(OperationException::class); (new AuthorizationPolicy())->assert_visible(new Principal(1,10,array('booking_manage_settings')),2,10); }
 public function test_capability_is_required(): void { $this->expectException(OperationException::class); (new AuthorizationPolicy())->assert_capability(new Principal(1,10),'booking_refund'); }
 public function test_explicit_capability(): void { (new AuthorizationPolicy())->assert_capability(new Principal(1,10,array('booking_refund')),'booking_refund'); $this->addToAssertionCount(1); }
}
