<?php
namespace SeanMorris\Ksqlc\Test;

use PHPUnit\Framework\TestCase
	, \InvalidArgumentException
	, \UnexpectedValueException
	, \SeanMorris\Ksqlc\Ksqlc
	, \SeanMorris\Ksqlc\Test\FakeHttp;

final class KsqlcTest extends TestCase
{
	public function testValidUrl()
	{
		$ksqlc = new Ksqlc('http://fake-valid-url:8088/');

		$this->assertInstanceOf(Ksqlc::CLASS, $ksqlc);
	}

	public function testInvalidUrl()
	{
		$this->expectException(InvalidArgumentException::class);

		$ksqlc = new \SeanMorris\Ksqlc\Ksqlc('invalid/:-url');
	}

	public function testRunFailure()
	{
		$this->expectException(UnexpectedValueException::class);

		$ksqlc = new FakeHttpKsqlc('http://fake-valid-url:8088/');

		$ksqlc->run('FAKE QUERY');
	}

	public function testEscapeString()
	{
		$ksqlc = new FakeHttpKsqlc('http://fake-valid-url:8088/');

		$this->assertEquals("FAKE ''QUERY'';", $ksqlc->escape("FAKE 'QUERY';"));
		$this->assertEquals("ANOTHER ''FAKE'' ''QUERY'';", $ksqlc->escape("ANOTHER 'FAKE' 'QUERY';"));
	}

	public function testStreamTableScanOption()
	{
		FakeHttp::$requests = [];

		$ksqlc = new FakeHttpKsqlc('http://fake-valid-url:8088/');
		$stream = $ksqlc->stream('SELECT * FROM `event_table`', 'earliest', FALSE, TRUE);
		$caught = FALSE;

		try
		{
			$stream->rewind();
		}
		catch(UnexpectedValueException $exception)
		{
			$caught = TRUE;
			$this->assertEquals(0, $exception->getCode());
		}

		$this->assertTrue($caught);
		$this->assertCount(1, FakeHttp::$requests);

		$payload = json_decode(FakeHttp::$requests[0]->content);

		$this->assertEquals('SELECT * FROM `event_table`;', $payload->ksql);
		$this->assertEquals('earliest', $payload->streamsProperties->{'ksql.streams.auto.offset.reset'});
		$this->assertSame('true', $payload->streamsProperties->{'ksql.query.pull.table.scan.enabled'});

		FakeHttp::$requests = [];

		$stream = $ksqlc->stream('SELECT * FROM `event_table`');
		$caught = FALSE;

		try
		{
			$stream->rewind();
		}
		catch(UnexpectedValueException $exception)
		{
			$caught = TRUE;
		}

		$this->assertTrue($caught);

		$payload = json_decode(FakeHttp::$requests[0]->content);

		$this->assertFalse(property_exists(
			$payload->streamsProperties
			, 'ksql.query.pull.table.scan.enabled'
		));
	}
}

class FakeHttpKsqlc extends Ksqlc { protected static $Http; }
FakeHttpKsqlc::inject(['Http' => FakeHttp::class]);
