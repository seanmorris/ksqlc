<?php
namespace SeanMorris\Ksqlc\Test;

use PHPUnit\Framework\TestCase, \SeanMorris\Ksqlc\Result;

final class ResultTest extends TestCase
{
	public function testParseResult()
    {
		$rawQueryResult = [
			'@type'         => 'queries',
			'statementText' => 'SHOW QUERIES;',
			'queries'       => [
				[
					'type'   => 'STREAM',
					'name'   => 'EVENT_STREAM',
					'topic'  => 'EVENTS',
					'format' => 'JSON'
				],
			],
			'warnings' => []
		];

		$result = new Result;
		$result->ingest($rawQueryResult);

		$this->assertEquals(
			$rawQueryResult['warnings']
			, $result->warnings
		);

		$this->assertEquals(
			$rawQueryResult['statementText']
			, $result->statementText
		);
    }

    public function testIterateType()
    {
    	$rawQueryResult = [
			'@type'         => 'queries',
			'statementText' => 'SHOW QUERIES;',
			'queries'       => [
				[
					'type'   => 'STREAM',
					'name'   => 'EVENT_STREAM',
					'topic'  => 'EVENTS',
					'format' => 'JSON'
				],[
					'type'   => 'STREAM',
					'name'   => 'EVENT_STREAM_2',
					'topic'  => 'EVENTS',
					'format' => 'JSON'
				],[
					'type'   => 'STREAM',
					'name'   => 'EVENT_STREAM_3',
					'topic'  => 'EVENTS',
					'format' => 'JSON'
				],
			],
			'warnings' => []
		];

		$result = new Result;
		$result->ingest($rawQueryResult);

		$count = 0;

		foreach($result as $key => $value)
		{
			$count++;
		}

		$this->assertEquals($count, 3);
    }
}
