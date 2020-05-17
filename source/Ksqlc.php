<?php
namespace SeanMorris\Ksqlc;

/**
 * Provides an interface to KSQLDB from PHP.
 */
class Ksqlc
{
	protected const HTTP_OK = 200;

	protected $endpoint;

	/**
	 * Return a new connection to KSQLDB.
	 * 
	 * @param string $endpoint The URL to KSQLDB's REST endpoint.
	 */
	public function __construct($endpoint)
	{
		if(!filter_var($endpoint, FILTER_VALIDATE_URL))
		{
			throw new \InvalidArgumentException(
				'Invalid endpoint.'
			);
		}

		$this->endpoint = $endpoint;
	}

	/**
	 * Return a new connection to KSQLDB.
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

		$string = implode(';', $strings) . ';';

		$response = $this->post('ksql', json_encode([
			'ksql' => $string
		]));

		$rawResponse = stream_get_contents($response->stream);

		if(!$response = json_decode($rawResponse))
		{
			throw new \UnexpectedValueException(
				'Unexpected formatting on query response.'
			);
		}

		if(is_object($response))
		{
			$response = [$response];
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
	public function stream($string, $offsetReset = 'latest')
	{
		$response = $this->post('query', json_encode([
			'ksql' => $string . ';'
			, 'streamsProperties' => [
				'ksql.streams.auto.offset.reset' => $offsetReset
			]
		]));

		if($response->code !== static::HTTP_OK)
		{
			throw new \UnexpectedValueException(
				'Unexpected HTTP response: '
					. PHP_EOL
					. stream_get_contents($response->stream)
				, $response->code
			);
		}

		stream_set_chunk_size($response->stream, 1);
		stream_set_read_buffer($response->stream, 0);

		while($message = fgets($response->stream))
		{
			if(!$message = rtrim($message))
			{
				continue;
			}

			$message = substr($message, 0, -1);

			[$message] = sscanf($message, '[%[^\0]');
			break;
		}

		if(!$record = json_decode($message))
		{
			throw new \UnexpectedValueException(
				'Unexpected formatting on first line of stream.'
			);
		}

		if(!($record->header ?? NULL) || !($record->header->schema ?? NULL))
		{
			throw new \UnexpectedValueException(
				'Unexpected data structure on first line of stream.'
			);
		}

		$keyTypes = [];
		$keyDefs  = explode(', ', $record->header->schema);

		foreach($keyDefs as $keyDef)
		{
			[$key, $type] = sscanf($keyDef, '`%[^\`]` %s');

			$keyTypes[ $key ] = $type;
		}

		$keys = array_keys($keyTypes);

		while($message = fgets($response->stream))
		{
			if(!$message = rtrim($message))
			{
				continue;
			}

			$message = substr($message, 0, -1);

			if(!$record = json_decode($message))
			{
				throw new \UnexpectedValueException(
					'Unexpected formatting in stream body.'
				);
			}

			if($record->finalMessage ?? 0)
			{
				break;
			}

			if(!($record->row ?? 0) || !($record->row->columns ?? 0))
			{
				throw new \UnexpectedValueException(
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

	/**
	 * Issue an HTTP GET request to the KSQLDB endpoint.
	 * 
	 * @param string $path The path to request
	 * @param object $content raw data to include with request
	 * 
	 * return object An object detailing the HTTP headers, with a readable STREAM containing the actual response body.
	 */
	protected function get($path, $content = NULL)
	{
		return $this->openRequest('GET', $path, $content);
	}

	/**
	 * Issue an HTTP POST request to the KSQLDB endpoint.
	 * 
	 * @param string $path The path to request
	 * @param object $content raw data to include with request
	 * 
	 * return object An object detailing the HTTP headers, with a readable STREAM containing the actual response body.
	 */
	protected function post($path, $content = NULL)
	{
		return $this->openRequest('POST', $path, $content);
	}

	/**
	 * Issue an HTTP request to the KSQLDB endpoint.
	 * 
	 * @param string $method The HTTP method to use.
	 * @param string $path The path to request
	 * @param object $content raw data to include with request
	 * 
	 * return object An object detailing the HTTP headers, with a readable STREAM containing the actual response body.
	 */
	protected function openRequest($method, $path, $content = NULL)
	{
		$context = stream_context_create(['http' => [
			'ignore_errors' => true
			, 'content'     => $content
			, 'method'      => $method
			, 'header'      => [
				'Content-Type: application/json; charset=utf-8'
				, 'Accept: application/vnd.ksql.v1+json'
			]
		]]);

		$handle = fopen(
			'http://ksql-server:8088/' . $path
			, 'r'
			, FALSE
			, $context
		);

		return array_reduce($http_response_header, function($carry, $header){

			if(stripos($header, 'HTTP/') === 0)
			{
				$header = strtoupper($header);

				[$httpVer, $code, $status] = sscanf(
					$header, 'HTTP/%s %s %[ -~]'
				);

				$spacePos = strpos($header, ' ');

				$carry->code   = (int) $code;
				$carry->http   = $httpVer;
				$carry->status = substr($header, 1 + $spacePos);
			}

			if(($split = stripos($header, ':')) !== FALSE)
			{
				$key   = substr($header, 0, $split);
				$value = substr($header, 1 + $split);

				$carry->header->$key = ltrim($value);
			}

			return $carry;

		}, (object) [
			'http'     => 0
			, 'code'   => 0
			, 'status' => 'ERROR'
			, 'header' => (object) []
			, 'stream' => $handle
		]);
	}
}
