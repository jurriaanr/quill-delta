<?php
/**
 * User: Jurriaan Ruitenberg
 * Date: 24-7-2018
 * Time: 12:31
 */

use Oberon\Quill\Delta\Delta;

class DeltaTest extends PHPUnit_Framework_TestCase {
	use \Madewithlove\PhpunitSnapshots\SnapshotAssertions;
	
	public function testCompose() {
		$this->assertEqualsSnapshot($this->getComposed()->getOps());
	}
	
	public function testToArray() {
		$this->assertEqualsSnapshot($this->getComposed()->toArray());
	}
	
	public function testToString() {
		$this->assertEqualsSnapshot((string)$this->getComposed());
	}
	
	public function testIsRetain() {
		$this->assertEquals(true, Delta::isRetain(['retain' => 1]));
		$this->assertEquals(false, Delta::isRetain(['retain' => 'x']));
		$this->assertEquals(false, Delta::isRetain(['retainx' => 1]));
	}
	
	public function testIsDelete() {
		$this->assertEquals(true, Delta::isDelete(['delete' => 1]));
		$this->assertEquals(false, Delta::isDelete(['delete' => 'x']));
		$this->assertEquals(false, Delta::isDelete(['deletex' => 1]));
	}
	
	public function testIsInsert() {
		$this->assertEquals(true, Delta::isInsert(['insert' => 'hello']));
		$this->assertEquals(false, Delta::isInsert(['insert' => 1]));
		$this->assertEquals(false, Delta::isInsert(['insertx' => 'hello']));
	}
	
	public function testHasAttributes() {
		$this->assertEquals(true, Delta::hasAttributes(['insert' => '', 'attributes' => []]));
		$this->assertEquals(false, Delta::hasAttributes(['insert' => '', 'attributes' => 'x']));
		$this->assertEquals(false, Delta::hasAttributes(['insert' => '']));
	}
	
	public function testGetAttributes() {
		$this->assertEquals([], Delta::getAttributes(['insert' => '', 'attributes' => []]));
		$this->assertEquals(['bold' => true], Delta::getAttributes(['insert' => '', 'attributes' => ['bold' => true]]));
		$this->assertEquals([], Delta::getAttributes(['insert' => '']));
	}
	
	/**
	 * @return Delta
	 */
	private function getComposed() {
		$ops1 = ["ops" => [
			["insert" => "hello"],
		]];
		$ops2 = ["ops" => [
			["retain" => 5],
			["insert" => " world"],
		]];
		
		return (new Delta($ops1))->compose(new Delta($ops2));
	}
}
