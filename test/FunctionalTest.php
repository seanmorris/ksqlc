<?php
namespace SeanMorris\Ksqlc\Test;

use PHPUnit\Framework\TestCase
	, \InvalidArgumentException
	, \SeanMorris\Ksqlc\Ksqlc
	, \SeanMorris\Ksqlc\Status
	, \SeanMorris\Ksqlc\Result
	, \SeanMorris\Ksqlc\Krest
	, \stdClass;

final class FunctionalTest extends TestCase
{
	private function _objectHasProperty($property, $object)
	{
		$this->assertTrue(property_exists($object, $property));
	}

	public function testInfo()
	{
		$ksqlc = new Ksqlc('http://ksql-server:8088');
		$info  = $ksqlc->info();

		$this->_objectHasProperty('kafkaClusterId', $info);
		$this->_objectHasProperty('ksqlServiceId', $info);
		$this->_objectHasProperty('version', $info);
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

		$this->_objectHasProperty('type', $streamCreated);
		$this->_objectHasProperty('warnings', $streamCreated);
		$this->_objectHasProperty('statementText', $streamCreated);

		list($streams) = $ksqlc->run('SHOW STREAMS');

		$this->assertInstanceOf(Result::CLASS, $streams);

		$this->_objectHasProperty('type', $streams);
		$this->_objectHasProperty('warnings', $streams);
		$this->_objectHasProperty('statementText', $streams);

		$describe = $ksqlc->run('DESCRIBE `event_stream`');

		$count = 100000;

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

		$this->_objectHasProperty('type', $streamDropped);
		$this->_objectHasProperty('warnings', $streamDropped);
		$this->_objectHasProperty('statementText', $streamDropped);
	}

	public function testTables()
	{
		$ksqlc = new Ksqlc('http://ksql-server:8088');

		list($dropIfExists) = $ksqlc->run(
			'DROP TABLE IF EXISTS `event_table`'
		);

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

		$this->_objectHasProperty('type', $tableCreated);
		$this->_objectHasProperty('warnings', $tableCreated);
		$this->_objectHasProperty('statementText', $tableCreated);

		list($tables) = $ksqlc->run('SHOW TABLES');

		$this->assertInstanceOf(Result::CLASS, $tables);

		$this->_objectHasProperty('type', $tables);
		$this->_objectHasProperty('warnings', $tables);
		$this->_objectHasProperty('statementText', $tables);

		list($describe) = $ksqlc->run('DESCRIBE `event_table`');
		list($tableDropped) = $ksqlc->run('DROP TABLE `event_table`');

		$this->assertInstanceOf(Status::CLASS, $tableDropped);

		$this->_objectHasProperty('type', $tableDropped);
		$this->_objectHasProperty('warnings', $tableDropped);
		$this->_objectHasProperty('statementText', $tableDropped);
	}

	public function testShowQueries()
	{
		$ksqlc = new Ksqlc('http://ksql-server:8088');

		list($queries) = $ksqlc->run('SHOW QUERIES');

		$this->assertInstanceOf(Result::CLASS, $queries);

		$this->_objectHasProperty('type', $queries);
		$this->_objectHasProperty('warnings', $queries);
		$this->_objectHasProperty('statementText', $queries);
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

		$this->_objectHasProperty('type', $streamCreated);
		$this->_objectHasProperty('warnings', $streamCreated);
		$this->_objectHasProperty('statementText', $streamCreated);

		$this->assertInstanceOf(Status::CLASS, $streamDropped);

		$this->_objectHasProperty('type', $streamDropped);
		$this->_objectHasProperty('warnings', $streamDropped);
		$this->_objectHasProperty('statementText', $streamDropped);
	}
}
