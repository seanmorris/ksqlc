<?php
namespace SeanMorris\Ksqlc\Test;

use PHPUnit\Framework\TestCase
	, \InvalidArgumentException
	, \UnexpectedValueException
	, \SeanMorris\Ksqlc\Krest
	, \SeanMorris\Ksqlc\Test\FakeHttp;

final class KrestTest extends TestCase
{
	public function testValidUrl()
	{
		$krest = new Krest('http://valid-url:8082/');
		$this->assertInstanceOf(Krest::CLASS, $krest);
	}

	public function testInvalidUrl()
	{
		$this->expectException(InvalidArgumentException::class);
		$krest = new \SeanMorris\Ksqlc\Krest('invalid/:-url');
	}

	public function testTopics()
	{
		$krest = new Krest('http://krest-server:8082');
		$topics = $krest->topics();
		$this->assertIsArray($topics);
	}

	public function testFailingTopics()
	{
		$this->expectException(UnexpectedValueException::class);
		$krest = new FakeHttpKrest('http://valid-url:8082/');
		$topics = $krest->topics();
	}

	public function testFailingProduce()
	{
		$this->expectException(UnexpectedValueException::class);
		$krest = new FakeHttpKrest('http://valid-url:8082/');
		$krest->produce('topic-name', 'record', 'record', 'record');
	}
}

class FakeHttpKrest extends Krest { protected static $Http; }
FakeHttpKrest::inject(['Http' => FakeHttp::class]);
