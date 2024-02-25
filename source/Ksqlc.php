<?php
namespace SeanMorris\Ksqlc;

use \InvalidArgumentException, \UnexpectedValueException;

/**
 * Provides an interface to KSQLDB from PHP.
 */
class Ksqlc
{
	const HTTP_OK = 200;
	protected $endpoint;
	protected static $Http;

	use Injectable;

	/**
	 * Return a new connection to KSQLDB.
	 *
	 * @param string $endpoint The URL to KSQLDB's REST endpoint.
	 */
	public function __construct($endpoint)
	{
		if(!filter_var($endpoint, FILTER_VALIDATE_URL))
		{
			throw new InvalidArgumentException(
				'Invalid endpoint.'
			);
		}

		$this->endpoint = $endpoint;
	}

	/**
	 * Return server info.
	 *
	 */
	public function info()
	{
		$http = static::$Http::get($this->endpoint . '/info');
		$json = stream_get_contents($http->stream);
		$body = json_decode($json);

		if(!$body)
		{
			throw new UnexpectedValueException(
				'Unexpected formatting on query response.'
			);
		}

		if(!$body->KsqlServerInfo)
		{
			throw new UnexpectedValueException(
				'Unexpected data structure on query response.'
			);
		}

		return $body->KsqlServerInfo;
	}

	/**
	 * Escape a string value for use in a KSQL query.
	 *
	 * @param string $endpoint The URL to KSQLDB's REST endpoint.
	 */
	public function escape($identifier)
	{
		return str_replace("'", "''", $identifier);
	}

	/**
	 * Run a KSQL statement.
	 *
	 * Use this method to do things like create
	 * or drop streams and tables. Pretty much
	 * everything but SELECT statments should be
	 * executed with Ksqcl::run()
	 *
	 * Ksqcl::run() will execute all parameters
	 * passed as queries and return an array
	 * of result objects.
	 *
	 * @param string ...$strings The KSQL statement to execute.
	 *
	 * @return array The list of result objects from KSQLDB.
	 */
	public function run(...$strings)
	{
		foreach($strings as $i => &$s)
		{
			if($i > 0 && is_array($s))
			{
				$s = sprintf(...$s);
			}
		}

		$string = implode('; ', $strings) . ';';

		$response = static::$Http::post(
			$this->endpoint . '/ksql'
			, json_encode([
				'ksql' => $string
			]
		));

		$rawResponse = stream_get_contents($response->stream);

		if(!$response = json_decode($rawResponse))
		{
			throw new UnexpectedValueException(
				'Unexpected formatting on query response.'
			);
		}

		if(!is_array($response))
		{
			$response = [$response];
		}

		foreach($response as &$r)
		{
			if(!isset($r->{ '@type' }))
			{
				$rr = new Status;
			}
			else
			{
				$typeSuffix = strtolower(substr($r->{'@type'},-6));

				if($r->{'@type'} === 'sourceDescription')
				{
					$rr = new Source;
				}
				else if($typeSuffix == 'status' || $typeSuffix == '_error')
				{
					$rr = new Status;
				}
				else
				{
					$rr = new Result;
				}
			}

			$rr->ingest($r);

			$r = $rr;
		}

		return $response;
	}

	/**
	 * Run an asyncronous KSQL query.
	 *
	 * Use this method to issue a select query
	 * and stream the results back to PHP.
	 *
	 * @param string $endpoint The KSQL statement to execute.
	 * @param string $offsetReset earliest/latest.
	 *
	 * @return Generator The generator that can be iterated for results.
	 */
	public function stream($string, $offsetReset = 'latest', $async = FALSE)
	{
		$response = static::$Http::post(
			$this->endpoint . '/query'
			, json_encode([
				'ksql' => $string . ';'
				, 'streamsProperties' => [
					'ksql.streams.auto.offset.reset' => $offsetReset
				]
			]
		));

		if($response->code !== HTTP::STATUS_OK)
		{
			throw new UnexpectedValueException(
				'Unexpected HTTP response: '
					. PHP_EOL
					. stream_get_contents($response->stream)
				, $response->code
			);
		}

		$buffer = NULL;

		while(!feof($response->stream))
		{
			$buffer .= fgets($response->stream);

			if(substr($buffer, -1) !== "\n")
			{
				continue;
			}

			if(!$buffer = rtrim($buffer))
			{
				continue;
			}

			$buffer = substr($buffer, 1, -1);

			break;
		}

		list($buffer) = sscanf($buffer, "[%[^[]]");

		$buffer = substr($buffer, 0, -1);

		stream_set_chunk_size($response->stream, 1);
		stream_set_read_buffer($response->stream, 0);
		stream_set_blocking($response->stream, !$async);

		if(!$record = json_decode($buffer))
		{
			throw new UnexpectedValueException(
				'Unexpected formatting on first line of stream.'
			);
		}

		if(!($record->header ?? NULL) || !($record->header->schema ?? NULL))
		{
			throw new UnexpectedValueException(
				'Unexpected data structure on first line of stream.'
			);
		}

		$keyTypes = [];
		$keyDefs  = explode(', ', $record->header->schema);

		foreach($keyDefs as $keyDef)
		{
			list($key, $type) = sscanf($keyDef, '`%[^\`]` %s');

			$keyTypes[ $key ] = $type;
		}

		$buffer = NULL;
		$keys   = array_keys($keyTypes);

		while(!feof($response->stream))
		{
			$buffer .= fgets($response->stream);

			if(substr($buffer, -1) !== "\n")
			{
				continue;
			}

			if($buffer === NULL)
			{
				continue;
			}

			if(!$buffer = rtrim($buffer))
			{
				continue;
			}

			$buffer = substr($buffer, 0, -1);

			if(!$record = json_decode($buffer))
			{
				throw new UnexpectedValueException(
					'Unexpected formatting in stream body.'
				);
			}

			$buffer = '';

			if($record->finalMessage ?? 0)
			{
				break;
			}

			if(!($record->row ?? 0) || !($record->row->columns ?? 0))
			{
				throw new UnexpectedValueException(
					'Unexpected data structure in stream body.'
				);
			}

			$entry = (object) array_combine(
				$keys, (array) $record->row->columns
			);

			yield $entry;
		}

		fclose($response->stream);
	}

	public function multiplex(...$queries)
	{
		$outerIterator = new StreamIterator();

		foreach($queries as $query)
		{
			if(!is_array($query))
			{
				$query = [$query];
			}

			$resultStream = $this->stream(...$query);

			$outerIterator->attachIterator($resultStream);
		}

		yield from $outerIterator;
	}
}

Ksqlc::inject(['Http' => Http::class]);
