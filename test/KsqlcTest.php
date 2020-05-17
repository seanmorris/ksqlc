<?php
namespace SeanMorris\Ksqlc\Test;

use PHPUnit\Framework\TestCase, \InvalidArgumentException, \SeanMorris\Ksqlc\Ksqlc;

final class KsqlcTest extends TestCase
{
	public function testGoodConnection()
	{
		$ksqlc = new Ksqlc('http://valid-url:8088/');

		$this->assertInstanceOf(Ksqlc::CLASS, $ksqlc);
	}

	public function testBadConnection()
	{
		$this->expectException(InvalidArgumentException::class);

		$ksqlc = new \SeanMorris\Ksqlc\Ksqlc('invalid/:-url');
	}
}
