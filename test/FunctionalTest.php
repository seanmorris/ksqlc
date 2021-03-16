<?php
namespace SeanMorris\Ksqlc\Test;

use PHPUnit\Framework\TestCase, \InvalidArgumentException, \SeanMorris\Ksqlc\Ksqlc, \SeanMorris\Ksqlc\Status, \SeanMorris\Ksqlc\Result, \SeanMorris\Ksqlc\Krest, \stdClass;

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

		list($dropIfExists) = $ksqlc->run(
			'DROP STREAM IF EXISTS `event_stream`'
		);

		list($streamCreated) = $ksqlc->run(
			"CREATE STREAM `event_stream` (
				`id` VARCHAR KEY,
				`body` VARCHAR,
				`created` DOUBLE
			) WITH (
				VALUE_FORMAT = 'JSON',
				KAFKA_TOPIC  = 'events',
				PARTITIONS   = 1
			)"
		);

		$this->assertEquals('SUCCESS', $streamCreated->status);

		$this->assertInstanceOf(Status::CLASS, $streamCreated);

		$this->assertObjectHasAttribute('type', $streamCreated);
		$this->assertObjectHasAttribute('warnings', $streamCreated);
		$this->assertObjectHasAttribute('statementText', $streamCreated);

		list($streams) = $ksqlc->run('SHOW STREAMS');

		$this->assertInstanceOf(Result::CLASS, $streams);

		$this->assertObjectHasAttribute('type', $streams);
		$this->assertObjectHasAttribute('warnings', $streams);
		$this->assertObjectHasAttribute('statementText', $streams);

		$describe = $ksqlc->run(
			'DESCRIBE `event_stream`'
			, 'DESCRIBE EXTENDED `event_stream`'
		);

		$delay = rand(1,1000) / 1000;
		$count = rand(1,10);

		$query = "SELECT *
			FROM  `event_stream`
			WHERE `body` = '%s'
			EMIT CHANGES
			LIMIT %d";

		$streamingResults = $ksqlc->multiplex(
			[sprintf($query, 'AAA', $count), 'earliest', TRUE]
			, [sprintf($query, 'BBB', $count), 'earliest', TRUE]
		);

		for($i = 0; $i < $count; $i++)
		{
			$recordA = (object) [
				'created' => microtime(true)
				, 'body'  => 'AAA'
				, 'id'    => uniqid()
			];

			$recordB = (object) [
				'created' => microtime(true)
				, 'body'  => 'BBB'
				, 'id'    => uniqid()
			];

			$recordC = (object) [
				'created' => microtime(true)
				, 'body'  => 'CCC'
				, 'id'    => uniqid()
			];

			$response = $krest->produce(
				'events'
				, $recordA
				, $recordB
				, $recordC
			);
		}

		$got = 0;

		foreach($streamingResults as $streamingResult)
		{
			if(!$streamingResult)
			{
				continue;
			}

			$this->assertInstanceOf(stdClass::CLASS, $streamingResult);

			$got++;
		}

		$this->assertEquals($got, 2 * $count);

		list($streamDropped) = $ksqlc->run('DROP STREAM `event_stream`');

		$this->assertInstanceOf(Status::CLASS, $streamDropped);

		$this->assertObjectHasAttribute('type', $streamDropped);
		$this->assertObjectHasAttribute('warnings', $streamDropped);
		$this->assertObjectHasAttribute('statementText', $streamDropped);
	}

	public function testTables()
	{
		$ksqlc = new Ksqlc('http://ksql-server:8088');

		list($tableCreated) = $ksqlc->run(
			"CREATE TABLE `event_table` (
				`id` VARCHAR PRIMARY KEY,
				`body` VARCHAR,
				`created` DOUBLE
			) WITH (
				KAFKA_TOPIC  = 'event_table',
				VALUE_FORMAT = 'json',
				PARTITIONS   = 1
			)"
		);

		$this->assertInstanceOf(Status::CLASS, $tableCreated);

		$this->assertObjectHasAttribute('type', $tableCreated);
		$this->assertObjectHasAttribute('warnings', $tableCreated);
		$this->assertObjectHasAttribute('statementText', $tableCreated);

		list($tables) = $ksqlc->run('SHOW TABLES');

		$this->assertInstanceOf(Result::CLASS, $tables);

		$this->assertObjectHasAttribute('type', $tables);
		$this->assertObjectHasAttribute('warnings', $tables);
		$this->assertObjectHasAttribute('statementText', $tables);

		$run = $ksqlc->run(
			'DESCRIBE `event_table`'
			, 'DESCRIBE EXTENDED `event_table`'
		);

		list($describe, $extended) = $run;

		list($tableDropped) = $ksqlc->run('DROP TABLE `event_table`');

		$this->assertInstanceOf(Status::CLASS, $tableDropped);

		$this->assertObjectHasAttribute('type', $tableDropped);
		$this->assertObjectHasAttribute('warnings', $tableDropped);
		$this->assertObjectHasAttribute('statementText', $tableDropped);
	}

	public function testShowQueries()
	{
		$ksqlc = new Ksqlc('http://ksql-server:8088');

		list($queries) = $ksqlc->run('SHOW QUERIES');

		$this->assertInstanceOf(Result::CLASS, $queries);

		$this->assertObjectHasAttribute('type', $queries);
		$this->assertObjectHasAttribute('warnings', $queries);
		$this->assertObjectHasAttribute('statementText', $queries);
	}

	public function testCreateAndDropAllAtOnce()
	{
		$ksqlc = new Ksqlc('http://ksql-server:8088');

		$creates = $ksqlc->run(
			"CREATE STREAM `event_stream3` (
				`id` VARCHAR,
				`body` VARCHAR,
				`created` DOUBLE
			) WITH (
				value_format = 'json',
				kafka_topic  = 'events',
				partitions   = 1
			)"
			, "DROP STREAM `event_stream3`"
		);

		list($streamCreated, $streamDropped) = $creates;

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
