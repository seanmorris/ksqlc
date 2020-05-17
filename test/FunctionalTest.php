<?php
namespace SeanMorris\Ksqlc\Test;

use PHPUnit\Framework\TestCase, \InvalidArgumentException, \SeanMorris\Ksqlc\Ksqlc, \SeanMorris\Ksqlc\Status, \SeanMorris\Ksqlc\Result, \SeanMorris\Ksqlc\Krest;

final class FunctionalTest extends TestCase
{
	public function testInfo()
	{
		$ksqlc = new Ksqlc('http://ksql-server:8088');
		$info  = $ksqlc->info();

		$this->assertObjectHasAttribute('kafkaClusterId', $info);
		$this->assertObjectHasAttribute('ksqlServiceId', $info);
		$this->assertObjectHasAttribute('version', $info);
	}

	public function testStreams()
	{
		$ksqlc = new Ksqlc('http://ksql-server:8088');
		$krest = new Krest('http://krest-server:8082');

		[$dropIfExists] = $ksqlc->run(
			'DROP STREAM IF EXISTS `event_stream`'
		);

		sleep(1);

		[$streamCreated] = $ksqlc->run(<<<EOQ
			CREATE STREAM `event_stream` (
				`id` VARCHAR,
				`body` VARCHAR,
				`created` DOUBLE
			) WITH (
				VALUE_FORMAT = 'json',
				KAFKA_TOPIC  = 'events',
				PARTITIONS   = 1
			)
			EOQ
		);

		sleep(1);

		$this->assertInstanceOf(Status::CLASS, $streamCreated);

		$this->assertObjectHasAttribute('type', $streamCreated);
		$this->assertObjectHasAttribute('warnings', $streamCreated);
		$this->assertObjectHasAttribute('statementText', $streamCreated);

		[$streams] = $ksqlc->run('SHOW STREAMS');

		$this->assertInstanceOf(Result::CLASS, $streams);

		$this->assertObjectHasAttribute('type', $streams);
		$this->assertObjectHasAttribute('warnings', $streams);
		$this->assertObjectHasAttribute('statementText', $streams);

		[$describe, $extended] = $ksqlc->run(
			'DESCRIBE `event_stream`'
			, 'DESCRIBE EXTENDED `event_stream`'
		);

		$delay = rand(1,1000) / 1000;
		$count = rand(1,10);

		$query = sprintf('SELECT * FROM `event_stream` EMIT CHANGES LIMIT %d', $count);

		$streamingResults = $ksqlc->stream($query);

		for($i = 0; $i < $count; $i++)
		{
			$krest->produce('events', (object)[
				'created' => microtime(true)
				, 'body'  => 'Test event @ ' . date('r')
				, 'id'    => uniqid()
			]);
		}

		$got = 0;

		foreach($streamingResults as $streamingResult)
		{
			$got++;
		}

		$this->assertEquals($got, $count);

		[$streamDropped] = $ksqlc->run('DROP STREAM `event_stream`');

		$this->assertInstanceOf(Status::CLASS, $streamDropped);

		$this->assertObjectHasAttribute('type', $streamDropped);
		$this->assertObjectHasAttribute('warnings', $streamDropped);
		$this->assertObjectHasAttribute('statementText', $streamDropped);
	}

	public function testTables()
	{
		$ksqlc = new Ksqlc('http://ksql-server:8088');

		[$tableCreated] = $ksqlc->run(<<<EOQ
			CREATE TABLE `event_table` (
				`id` VARCHAR,
				`body` VARCHAR,
				`created` DOUBLE
			) WITH (
				KAFKA_TOPIC  = 'event_table',
				VALUE_FORMAT = 'json',
				PARTITIONS   = 1,
				KEY          = '`id`'
			)
			EOQ
		);

		$this->assertInstanceOf(Status::CLASS, $tableCreated);

		$this->assertObjectHasAttribute('type', $tableCreated);
		$this->assertObjectHasAttribute('warnings', $tableCreated);
		$this->assertObjectHasAttribute('statementText', $tableCreated);

		[$tables] = $ksqlc->run('SHOW TABLES');

		$this->assertInstanceOf(Result::CLASS, $tables);

		$this->assertObjectHasAttribute('type', $tables);
		$this->assertObjectHasAttribute('warnings', $tables);
		$this->assertObjectHasAttribute('statementText', $tables);

		[$describe, $extended] = $ksqlc->run(
			'DESCRIBE `event_table`'
			, 'DESCRIBE EXTENDED `event_table`'
		);

		[$tableDropped] = $ksqlc->run('DROP TABLE `event_table`');

		$this->assertInstanceOf(Status::CLASS, $tableDropped);

		$this->assertObjectHasAttribute('type', $tableDropped);
		$this->assertObjectHasAttribute('warnings', $tableDropped);
		$this->assertObjectHasAttribute('statementText', $tableDropped);
	}

	public function testShowQueries()
	{
		$ksqlc = new Ksqlc('http://ksql-server:8088');

		[$queries] = $ksqlc->run('SHOW QUERIES');

		$this->assertInstanceOf(Result::CLASS, $queries);

		$this->assertObjectHasAttribute('type', $queries);
		$this->assertObjectHasAttribute('warnings', $queries);
		$this->assertObjectHasAttribute('statementText', $queries);
	}

	public function testCreateAndDropAllAtOnce()
	{
		$ksqlc = new Ksqlc('http://ksql-server:8088');

		[$streamCreated, $streamDropped] = $ksqlc->run(<<<EOQ
			CREATE STREAM `event_stream3` (
				`id` VARCHAR,
				`body` VARCHAR,
				`created` DOUBLE
			) WITH (
				value_format = 'json',
				kafka_topic  = 'events',
				partitions   = 1
			)
			EOQ
			, 'DROP STREAM `event_stream3`'
		);

		$this->assertInstanceOf(Status::CLASS, $streamCreated);

		$this->assertObjectHasAttribute('type', $streamCreated);
		$this->assertObjectHasAttribute('warnings', $streamCreated);
		$this->assertObjectHasAttribute('statementText', $streamCreated);

		$this->assertInstanceOf(Status::CLASS, $streamDropped);

		$this->assertObjectHasAttribute('type', $streamDropped);
		$this->assertObjectHasAttribute('warnings', $streamDropped);
		$this->assertObjectHasAttribute('statementText', $streamDropped);
	}
}
