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

	public function testEscapeString()
	{
		$ksqlc = new Ksqlc('http://valid-url:8088/');

		$this->assertEquals("FAKE ''QUERY'';", $ksqlc->escape("FAKE 'QUERY';"));
		$this->assertEquals("ANOTHER ''FAKE'' ''QUERY'';", $ksqlc->escape("ANOTHER 'FAKE' 'QUERY';"));
	}
}
