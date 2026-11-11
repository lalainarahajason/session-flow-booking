<?php
use PHPUnit\Framework\TestCase;
use SessionFlowBookings\WordPress\Rest\DtoValidator;
use SessionFlowBookings\Domain\OperationException;
final class DtoValidatorTest extends TestCase {
 private function schema(): array { return array( 'type'=>'object','additionalProperties'=>false,'required'=>array('config'),'properties'=>array('config'=>array('type'=>'object','additionalProperties'=>false,'required'=>array('count'),'properties'=>array('count'=>array('type'=>'integer','minimum'=>1,'maximum'=>5)))) ); }
 public function test_nested_valid_dto(): void { ( new DtoValidator() )->validate( json_decode('{"config":{"count":1}}'),$this->schema() ); $this->addToAssertionCount(1); }
 #[\PHPUnit\Framework\Attributes\DataProvider('invalid')]
 public function test_closed_and_strict(string $json): void { $this->expectException(OperationException::class); (new DtoValidator())->validate(json_decode($json),$this->schema()); }
 public static function invalid(): array { return array(array('{"config":{"count":"1"}}'),array('{"config":{"count":1,"status":"paid"}}'),array('{"config":{"count":0}}'),array('{"config":[]}'),array('{"config":null}'),array('{"config":{}}'),array('{"config":{"count":1},"buyer_id":"x"}')); }
 public function test_empty_object_is_not_empty_array(): void { (new DtoValidator())->validate((object)array(),array('type'=>'object','properties'=>array())); $this->expectException(OperationException::class); (new DtoValidator())->validate(array(),array('type'=>'object','properties'=>array())); }
}
