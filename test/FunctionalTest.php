<?php
namespace SeanMorris\Ksqlc\Test;

use PHPUnit\Framework\TestCase, \InvalidArgumentException, \SeanMorris\Ksqlc\Ksqlc, \SeanMorris\Ksqlc\Status, \SeanMorris\Ksqlc\Result;

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

	public function testCreate()
	{
		$ksqlc = new Ksqlc('http://ksql-server:8088');
		
		[$streamCreated] = $ksqlc->run(<<<EOQ
			CREATE STREAM `event_stream` (
				`id` VARCHAR,
				`body` VARCHAR,
				`created` DOUBLE
			) WITH (
				value_format = 'json',
				kafka_topic  = 'events',
				partitions   = 1
			)
			EOQ
		);

		var_dump($streamCreated);

		$this->assertInstanceOf(Status::CLASS, $streamCreated);

		$this->assertObjectHasAttribute('type', $streamCreated);
		$this->assertObjectHasAttribute('warnings', $streamCreated);
		$this->assertObjectHasAttribute('statementText', $streamCreated);

		[$tableCreated] = $ksqlc->run(<<<EOQ
			CREATE STREAM `event_table` (
				`id` VARCHAR,
				`body` VARCHAR,
				`created` DOUBLE
			) WITH (
				value_format = 'json',
				key          = '`id`'
			)
			EOQ
		);

		$this->assertInstanceOf(Status::CLASS, $streamCreated);

		$this->assertObjectHasAttribute('type', $tableCreated);
		$this->assertObjectHasAttribute('warnings', $tableCreated);
		$this->assertObjectHasAttribute('statementText', $tableCreated);
	}

	public function testShowStreams()
	{
		$ksqlc = new Ksqlc('http://ksql-server:8088');
		
		[$streams] = $ksqlc->run('SHOW STREAMS');

		$this->assertInstanceOf(Result::CLASS, $streams);

		$this->assertObjectHasAttribute('type', $streams);
		$this->assertObjectHasAttribute('warnings', $streams);
		$this->assertObjectHasAttribute('statementText', $streams);
	}

	public function testShowTables()
	{
		$ksqlc = new Ksqlc('http://ksql-server:8088');
		
		[$tables] = $ksqlc->run('SHOW TABLES');

		$this->assertInstanceOf(Result::CLASS, $tables);

		$this->assertObjectHasAttribute('type', $tables);
		$this->assertObjectHasAttribute('warnings', $tables);
		$this->assertObjectHasAttribute('statementText', $tables);
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

	public function testCreateAndDrop()
	{
		$ksqlc = new Ksqlc('http://ksql-server:8088');
		
		[$streamCreated] = $ksqlc->run(<<<EOQ
			CREATE STREAM `event_stream` (
				`id` VARCHAR,
				`body` VARCHAR,
				`created` DOUBLE
			) WITH (
				value_format = 'json',
				kafka_topic  = 'events',
				partitions   = 1
			)
			EOQ
		);
		
		$this->assertInstanceOf(Status::CLASS, $streamCreated);

		$this->assertObjectHasAttribute('type', $streamCreated);
		$this->assertObjectHasAttribute('warnings', $streamCreated);
		$this->assertObjectHasAttribute('statementText', $streamCreated);

		[$streamDropped] = $ksqlc->run('DROP STREAM `event_stream`');

		$this->assertInstanceOf(Status::CLASS, $streamDropped);

		$this->assertObjectHasAttribute('type', $streamDropped);
		$this->assertObjectHasAttribute('warnings', $streamDropped);
		$this->assertObjectHasAttribute('statementText', $streamDropped);
	}

	public function testCreateAndDropAllAtOnce()
	{
		$ksqlc = new Ksqlc('http://ksql-server:8088');
		
		[$streamCreated, $streamDropped] = $ksqlc->run(<<<EOQ
			CREATE STREAM `event_stream` (
				`id` VARCHAR,
				`body` VARCHAR,
				`created` DOUBLE
			) WITH (
				value_format = 'json',
				kafka_topic  = 'events',
				partitions   = 1
			)
			EOQ
			, 'DROP STREAM `event_stream`'
		);

		var_dump($streamCreated, $streamDropped);
		
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
