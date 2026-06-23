<?php
namespace SeanMorris\Ksqlc\Test;

use PHPUnit\Framework\TestCase
	, \ArrayIterator
	, \BadMethodCallException
	, \InvalidArgumentException
	, \RuntimeException
	, \UnexpectedValueException
	, \stdClass
	, \SeanMorris\Ksqlc\Error
	, \SeanMorris\Ksqlc\Http
	, \SeanMorris\Ksqlc\Injectable
	, \SeanMorris\Ksqlc\Krest
	, \SeanMorris\Ksqlc\Ksqlc
	, \SeanMorris\Ksqlc\Result
	, \SeanMorris\Ksqlc\Source
	, \SeanMorris\Ksqlc\Status
	, \SeanMorris\Ksqlc\StreamIterator;

final class CoverageTest extends TestCase
{
	protected static $httpProcess;
	protected static $httpUrl;
	protected static $httpDir;

	/**
	 * @before
	 */
	public function resetFakeHttp()
	{
		FakeHttp::$requests  = [];
		FakeHttp::$responses = [];
	}

	/**
	 * @afterClass
	 */
	public static function stopHttpServer()
	{
		if(self::$httpProcess)
		{
			proc_terminate(self::$httpProcess);
			proc_close(self::$httpProcess);
		}

		if(self::$httpDir && is_dir(self::$httpDir))
		{
			@unlink(self::$httpDir . '/router.php');
			@rmdir(self::$httpDir);
		}
	}

	public function testInfoParsesServerInfo()
	{
		FakeHttp::$responses[] = [
			'code' => Http::STATUS_OK,
			'body' => json_encode([
				'KsqlServerInfo' => [
					'kafkaClusterId' => 'cluster',
					'ksqlServiceId'  => 'service',
					'version'        => '1.2.3',
				]
			])
		];

		$ksqlc = new CoverageKsqlc('http://ksql-server:8088');
		$info = $ksqlc->info();

		$this->assertEquals('1.2.3', $info->version);
		$this->assertEquals('GET', FakeHttp::$requests[0]->method);
		$this->assertEquals('http://ksql-server:8088/info', FakeHttp::$requests[0]->url);
		$this->assertEquals(['Connection: close'], FakeHttp::$requests[0]->headers);
	}

	public function testInfoRejectsMalformedJson()
	{
		$this->expectException(UnexpectedValueException::class);

		FakeHttp::$responses[] = ['body' => 'not json'];

		$ksqlc = new CoverageKsqlc('http://ksql-server:8088');
		$ksqlc->info();
	}

	public function testInfoRejectsUnexpectedStructure()
	{
		$this->expectException(UnexpectedValueException::class);

		FakeHttp::$responses[] = ['body' => '{"KsqlServerInfo":null}'];

		$ksqlc = new CoverageKsqlc('http://ksql-server:8088');
		$ksqlc->info();
	}

	public function testRunBuildsQueriesAndMapsResponseTypes()
	{
		$response = [
			(object) [
				'@type' => 'currentStatus',
				'commandStatus' => (object) [
					'message' => 'Stream created',
					'status'  => 'SUCCESS',
				],
				'commandId' => 'stream/created',
				'statementText' => 'CREATE STREAM',
				'warnings' => [],
				'currentStatus' => (object) ['status' => 'SUCCESS'],
			],
			(object) [
				'@type' => 'queries',
				'statementText' => 'SHOW QUERIES;',
				'queries' => [
					(object) ['id' => 'query-1'],
					(object) ['id' => 'query-2'],
				],
				'warnings' => [],
			],
			(object) [
				'@type' => 'sourceDescription',
				'statementText' => 'DESCRIBE EVENT_STREAM;',
				'warnings' => [],
				'sourceDescription' => (object) [
					'name' => 'EVENT_STREAM',
					'type' => 'STREAM',
					'topic' => 'events',
					'fields' => [(object) ['name' => 'ID']],
					'readQueries' => [],
					'writeQueries' => [],
				],
			],
			(object) [
				'@type' => 'query_error',
				'error_code' => 40001,
				'statementText' => 'BAD QUERY;',
				'warnings' => [],
			],
		];

		FakeHttp::$responses[] = [
			'code' => Http::STATUS_OK,
			'body' => json_encode($response)
		];

		$ksqlc = new CoverageKsqlc('http://ksql-server:8088');
		$results = $ksqlc->run(
			'CREATE STREAM EVENT_STREAM',
			['DROP STREAM %s', 'OLD_STREAM']
		);

		$payload = json_decode(FakeHttp::$requests[0]->content);

		$this->assertEquals(
			'CREATE STREAM EVENT_STREAM; DROP STREAM OLD_STREAM;',
			$payload->ksql
		);

		$this->assertInstanceOf(Status::class, $results[0]);
		$this->assertEquals('SUCCESS', $results[0]->status);
		$this->assertEquals('Stream created', $results[0]->message);

		$this->assertInstanceOf(Result::class, $results[1]);
		$this->assertCount(2, iterator_to_array($results[1]));

		$this->assertInstanceOf(Source::class, $results[2]);
		$this->assertEquals('EVENT_STREAM', $results[2]->name);
		$this->assertEquals('events', $results[2]->topic);

		$this->assertInstanceOf(Status::class, $results[3]);
		$this->assertEquals(40001, $results[3]->error_code);
	}

	public function testRunWrapsSingleObjectResponse()
	{
		FakeHttp::$responses[] = [
			'body' => json_encode((object) [
				'@type' => 'queries',
				'statementText' => 'SHOW QUERIES;',
				'queries' => [],
				'warnings' => [],
			])
		];

		$ksqlc = new CoverageKsqlc('http://ksql-server:8088');
		$results = $ksqlc->run('SHOW QUERIES');

		$this->assertCount(1, $results);
		$this->assertInstanceOf(Result::class, $results[0]);
	}

	public function testRunRejectsResponseWithoutType()
	{
		$this->expectException(InvalidArgumentException::class);

		FakeHttp::$responses[] = ['body' => '[{"statementText":"SHOW QUERIES;"}]'];

		$ksqlc = new CoverageKsqlc('http://ksql-server:8088');
		$ksqlc->run('SHOW QUERIES');
	}

	public function testStreamParsesRowsAndClosesOnFinalMessage()
	{
		FakeHttp::$responses[] = [
			'code' => Http::STATUS_OK,
			'body' => "\n" . $this->streamBody(
				'`ID` STRING, `BODY` STRING',
				[
					['one', 'alpha'],
					['two', 'beta'],
				]
			)
		];

		$ksqlc = new CoverageKsqlc('http://ksql-server:8088');
		$rows = iterator_to_array($ksqlc->stream('SELECT * FROM EVENT_STREAM', 'earliest'));

		$payload = json_decode(FakeHttp::$requests[0]->content);

		$this->assertEquals('SELECT * FROM EVENT_STREAM;', $payload->ksql);
		$this->assertEquals('earliest', $payload->streamsProperties->{'ksql.streams.auto.offset.reset'});
		$this->assertCount(2, $rows);
		$this->assertEquals('one', $rows[0]->ID);
		$this->assertEquals('alpha', $rows[0]->BODY);
		$this->assertEquals('two', $rows[1]->ID);
		$this->assertEquals('beta', $rows[1]->BODY);
	}

	public function testStreamRejectsHttpError()
	{
		$this->expectException(UnexpectedValueException::class);
		$this->expectExceptionCode(500);

		FakeHttp::$responses[] = [
			'code' => 500,
			'body' => 'server exploded'
		];

		$ksqlc = new CoverageKsqlc('http://ksql-server:8088');
		iterator_to_array($ksqlc->stream('SELECT * FROM EVENT_STREAM'));
	}

	public function testStreamRejectsMalformedHeader()
	{
		$this->expectException(UnexpectedValueException::class);

		FakeHttp::$responses[] = [
			'code' => Http::STATUS_OK,
			'body' => "[not json,\n"
		];

		$ksqlc = new CoverageKsqlc('http://ksql-server:8088');
		iterator_to_array($ksqlc->stream('SELECT * FROM EVENT_STREAM'));
	}

	public function testStreamRejectsHeaderWithoutSchema()
	{
		$this->expectException(UnexpectedValueException::class);

		FakeHttp::$responses[] = [
			'code' => Http::STATUS_OK,
			'body' => "[{\"header\":{}},\n{\"finalMessage\":\"done\"}]\n"
		];

		$ksqlc = new CoverageKsqlc('http://ksql-server:8088');
		iterator_to_array($ksqlc->stream('SELECT * FROM EVENT_STREAM'));
	}

	public function testStreamRejectsMalformedBody()
	{
		$this->expectException(UnexpectedValueException::class);

		FakeHttp::$responses[] = [
			'code' => Http::STATUS_OK,
			'body' => "[{\"header\":{\"schema\":\"`ID` STRING\"}},\nnot json,\n"
		];

		$ksqlc = new CoverageKsqlc('http://ksql-server:8088');
		iterator_to_array($ksqlc->stream('SELECT * FROM EVENT_STREAM'));
	}

	public function testStreamRejectsBodyWithoutColumns()
	{
		$this->expectException(UnexpectedValueException::class);

		FakeHttp::$responses[] = [
			'code' => Http::STATUS_OK,
			'body' => "[{\"header\":{\"schema\":\"`ID` STRING\"}},\n{\"row\":{}},\n"
		];

		$ksqlc = new CoverageKsqlc('http://ksql-server:8088');
		iterator_to_array($ksqlc->stream('SELECT * FROM EVENT_STREAM'));
	}

	public function testMultiplexInterleavesStreams()
	{
		FakeHttp::$responses[] = [
			'code' => Http::STATUS_OK,
			'body' => $this->streamBody('`ID` STRING', [['one']])
		];

		FakeHttp::$responses[] = [
			'code' => Http::STATUS_OK,
			'body' => $this->streamBody('`ID` STRING', [['two']])
		];

		$ksqlc = new CoverageKsqlc('http://ksql-server:8088');
		$rows = iterator_to_array($ksqlc->multiplex(
			['SELECT * FROM A', 'earliest', TRUE],
			['SELECT * FROM B', 'latest', FALSE]
		), FALSE);

		$ids = [];

		foreach($rows as $row)
		{
			if($row)
			{
				$ids[] = $row->ID;
			}
		}

		sort($ids);

		$this->assertEquals(['one', 'two'], $ids);
	}

	public function testMultiplexAcceptsScalarQuery()
	{
		FakeHttp::$responses[] = [
			'code' => Http::STATUS_OK,
			'body' => $this->streamBody('`ID` STRING', [['one']])
		];

		$ksqlc = new CoverageKsqlc('http://ksql-server:8088');
		$rows = iterator_to_array($ksqlc->multiplex('SELECT * FROM A'), FALSE);

		$this->assertCount(1, $rows);
		$this->assertEquals('one', $rows[0]->ID);
	}

	public function testStreamSkipsBlankBodyLines()
	{
		FakeHttp::$responses[] = [
			'code' => Http::STATUS_OK,
			'body' => "[{\"header\":{\"schema\":\"`ID` STRING\"}},\n\n{\"row\":{\"columns\":[\"one\"]}},\n{\"finalMessage\":\"done\"}]\n"
		];

		$ksqlc = new CoverageKsqlc('http://ksql-server:8088');
		$rows = iterator_to_array($ksqlc->stream('SELECT * FROM EVENT_STREAM'));

		$this->assertCount(1, $rows);
		$this->assertEquals('one', $rows[0]->ID);
	}

	public function testStreamAcceptsHeaderWithoutTrailingNewline()
	{
		FakeHttp::$responses[] = [
			'code' => Http::STATUS_OK,
			'body' => '{"header":{"schema":"`ID` STRING"}}'
		];

		$ksqlc = new CoverageKsqlc('http://ksql-server:8088');
		$rows = iterator_to_array($ksqlc->stream('SELECT * FROM EVENT_STREAM'));

		$this->assertEquals([], $rows);
	}

	public function testStreamIgnoresPartialBodyLineAtEof()
	{
		FakeHttp::$responses[] = [
			'code' => Http::STATUS_OK,
			'body' => "[{\"header\":{\"schema\":\"`ID` STRING\"}},\n{\"row\":{\"columns\":[\"one\"]}}"
		];

		$ksqlc = new CoverageKsqlc('http://ksql-server:8088');
		$rows = iterator_to_array($ksqlc->stream('SELECT * FROM EVENT_STREAM'));

		$this->assertEquals([], $rows);
	}

	public function testKrestTopicsParsesSuccessfulResponse()
	{
		FakeHttp::$responses[] = [
			'code' => Http::STATUS_OK,
			'body' => '["events","orders"]'
		];

		$krest = new CoverageKrest('http://krest-server:8082');
		$topics = $krest->topics();

		$this->assertEquals(['events', 'orders'], $topics);
		$this->assertEquals('GET', FakeHttp::$requests[0]->method);
		$this->assertEquals('http://krest-server:8082/topics', FakeHttp::$requests[0]->url);
	}

	public function testKrestTopicsRejectsMalformedJson()
	{
		$this->expectException(UnexpectedValueException::class);

		FakeHttp::$responses[] = [
			'code' => Http::STATUS_OK,
			'body' => 'not json'
		];

		$krest = new CoverageKrest('http://krest-server:8082');
		$krest->topics();
	}

	public function testKrestProduceWrapsRecords()
	{
		FakeHttp::$responses[] = [
			'code' => Http::STATUS_OK,
			'body' => '{"offsets":[]}'
		];

		$krest = new CoverageKrest('http://krest-server:8082');
		$response = $krest->produce(
			'events',
			(object) ['id' => 'one'],
			(object) ['id' => 'two']
		);

		$payload = json_decode(FakeHttp::$requests[0]->content);

		$this->assertEquals(Http::STATUS_OK, $response->code);
		$this->assertEquals('POST', FakeHttp::$requests[0]->method);
		$this->assertEquals('http://krest-server:8082/topics/events', FakeHttp::$requests[0]->url);
		$this->assertEquals('one', $payload->records[0]->value->id);
		$this->assertEquals('two', $payload->records[1]->value->id);
	}

	public function testIngestorRejectsMissingType()
	{
		$this->expectException(InvalidArgumentException::class);

		$result = new Result;
		$result->ingest(['queries' => []]);
	}

	public function testIngestorRejectsSecondIngest()
	{
		$this->expectException(BadMethodCallException::class);

		$result = new Result;
		$result->ingest(['@type' => 'queries', 'queries' => []]);
		$result->ingest(['@type' => 'queries', 'queries' => []]);
	}

	public function testResponseIteratesSingularResult()
	{
		$status = new Status;
		$status->ingest([
			'@type' => 'currentStatus',
			'currentStatus' => (object) ['status' => 'SUCCESS'],
		]);

		$values = iterator_to_array($status->getIterator());

		$this->assertCount(1, $values);
		$this->assertEquals('SUCCESS', $values[0]->status);
	}

	public function testResponseIteratesEmptyResult()
	{
		$result = new Result;
		$result->ingest([
			'@type' => 'queries',
			'statementText' => 'SHOW QUERIES;',
			'warnings' => [],
		]);

		$this->assertEquals([], iterator_to_array($result));
	}

	public function testSourceMapsSourceDescription()
	{
		$source = new Source;
		$source->ingest((object) [
			'@type' => 'sourceDescription',
			'sourceDescription' => (object) [
				'name' => 'EVENT_STREAM',
				'type' => 'STREAM',
				'windowType' => 'SESSION',
				'topic' => 'events',
				'partitions' => 3,
				'ignoredProperty' => 'ignored',
				'readQueries' => [],
				'writeQueries' => [],
			]
		]);

		$this->assertEquals('EVENT_STREAM', $source->name);
		$this->assertEquals('STREAM', $source->type);
		$this->assertEquals('SESSION', $source->windowType);
		$this->assertEquals(3, $source->partitions);
	}

	public function testErrorIngestsErrorCode()
	{
		$error = new Error;
		$error->ingest([
			'@type' => 'ksql_error',
			'error_code' => 40001,
		]);

		$this->assertEquals(40001, $error->error_code);
	}

	public function testStreamIteratorInterleavesAttachedIterators()
	{
		$iterator = new StreamIterator();
		$iterator->attachIterator(new ArrayIterator(['a', 'b']));
		$iterator->attachIterator(new ArrayIterator(['c', 'd']));

		$values = [];

		foreach($iterator as $value)
		{
			$values[] = $value;
		}

		$this->assertEquals(['a', 'c', 'b', 'd'], $values);
	}

	public function testInjectableSetsStaticDependencies()
	{
		CoverageInjectable::inject(['Dependency' => stdClass::class]);

		$this->assertEquals(stdClass::class, CoverageInjectable::dependency());
	}

	public function testHttpGetParsesStatusHeadersAndBody()
	{
		self::startHttpServer();

		$response = Http::get(self::$httpUrl . '/get');
		$body = json_decode(stream_get_contents($response->stream));

		$this->assertEquals(200, $response->code);
		$this->assertEquals('1.1', $response->http);
		$this->assertEquals('200 OK', $response->status);
		$this->assertEquals('GET', $response->method);
		$this->assertEquals(self::$httpUrl . '/get', $response->url);
		$this->assertTrue($response->time > 0);
		$this->assertEquals('GET', $response->header->{'X-Test-Method'});
		$this->assertEquals('GET', $body->method);
		$this->assertNull($body->contentType);
		$this->assertEquals(
			'application/vnd.kafka.v2+json, application/vnd.kafka+json, application/json',
			$body->accept
		);
	}

	public function testHttpPostSendsBodyAndCustomHeaders()
	{
		self::startHttpServer();

		$response = Http::post(
			self::$httpUrl . '/post',
			'payload',
			['X-Custom: present']
		);

		$body = json_decode(stream_get_contents($response->stream));

		$this->assertEquals(202, $response->code);
		$this->assertEquals('202 ACCEPTED', $response->status);
		$this->assertEquals('POST', $response->header->{'X-Test-Method'});
		$this->assertEquals('POST', $body->method);
		$this->assertEquals('payload', $body->body);
		$this->assertEquals('application/vnd.kafka.json.v2+json', $body->contentType);
		$this->assertEquals('present', $body->custom);
	}

	private function streamBody($schema, $rows)
	{
		$body = '[{"header":{"schema":"' . addslashes($schema) . "\"}},\n";

		foreach($rows as $row)
		{
			$body .= json_encode((object) [
				'row' => (object) ['columns' => $row]
			]) . ",\n";
		}

		return $body . "{\"finalMessage\":\"done\"}]\n";
	}

	private static function startHttpServer()
	{
		if(self::$httpUrl)
		{
			return;
		}

		if(!function_exists('proc_open'))
		{
			self::markTestSkipped('proc_open is required for HTTP wrapper coverage.');
		}

		$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);

		if(!$socket)
		{
			throw new RuntimeException($errstr, $errno);
		}

		$name = stream_socket_get_name($socket, FALSE);
		fclose($socket);

		$parts = explode(':', $name);
		$port  = array_pop($parts);

		self::$httpDir = sys_get_temp_dir() . '/ksqlc-http-' . getmypid() . '-' . $port;
		mkdir(self::$httpDir);

		file_put_contents(self::$httpDir . '/router.php', <<<'PHP'
<?php
$body = file_get_contents('php://input');

header('X-Test-Method: ' . $_SERVER['REQUEST_METHOD']);
header('X-Test-Body-Length: ' . strlen($body));
http_response_code($_SERVER['REQUEST_METHOD'] === 'POST' ? 202 : 200);

echo json_encode([
	'method'      => $_SERVER['REQUEST_METHOD'],
	'body'        => $body,
	'contentType' => $_SERVER['CONTENT_TYPE'] ?? NULL,
	'accept'      => $_SERVER['HTTP_ACCEPT'] ?? NULL,
	'custom'      => $_SERVER['HTTP_X_CUSTOM'] ?? NULL,
]);
PHP
		);

		$command = escapeshellarg(PHP_BINARY)
			. ' -S 127.0.0.1:' . $port
			. ' ' . escapeshellarg(self::$httpDir . '/router.php');

		self::$httpProcess = proc_open($command, [
			0 => ['pipe', 'r'],
			1 => ['file', self::$httpDir . '/stdout.log', 'a'],
			2 => ['file', self::$httpDir . '/stderr.log', 'a'],
		], $pipes);

		if(!is_resource(self::$httpProcess))
		{
			throw new RuntimeException('Could not start HTTP test server.');
		}

		self::$httpUrl = 'http://127.0.0.1:' . $port;

		for($i = 0; $i < 50; $i++)
		{
			$connection = @fsockopen('127.0.0.1', $port);

			if($connection)
			{
				fclose($connection);
				return;
			}

			usleep(100000);
		}

		throw new RuntimeException('HTTP test server did not start.');
	}
}

class CoverageKsqlc extends Ksqlc { protected static $Http; }
CoverageKsqlc::inject(['Http' => FakeHttp::class]);

class CoverageKrest extends Krest { protected static $Http; }
CoverageKrest::inject(['Http' => FakeHttp::class]);

class CoverageInjectable
{
	use Injectable;

	protected static $Dependency;

	public static function dependency()
	{
		return static::$Dependency;
	}
}
