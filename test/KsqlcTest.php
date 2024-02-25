<?php
namespace SeanMorris\Ksqlc\Test;

use PHPUnit\Framework\TestCase
	, \InvalidArgumentException
	, \SeanMorris\Ksqlc\Ksqlc
	, \SeanMorris\Ksqlc\Test\FakeHttp;

final class KsqlcTest extends TestCase
{
	public function testValidUrl()
	{
		$ksqlc = new Ksqlc('http://valid-url:8088/');

		$this->assertInstanceOf(Ksqlc::CLASS, $ksqlc);
	}

	public function testInvalidUrl()
	{
		$this->expectException(InvalidArgumentException::class);

		$ksqlc = new \SeanMorris\Ksqlc\Ksqlc('invalid/:-url');
	}
}
