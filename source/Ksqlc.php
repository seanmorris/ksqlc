<?php
namespace SeanMorris\Ksqlc;
class Ksqlc
{
	protected const
		HTTP_OK = 200
	;

	protected $endpoint;

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

	public static function escape($identifier)
	{
		return str_replace("'", "''", $identifier);
	}

	public static function run(...$strings)
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

	public static function stream($string, $reset = 'latest')
	{
		$response = $this->post('query', json_encode([
			'ksql' => $string . ';'
			, 'streamsProperties' => [
				'ksql.streams.auto.offset.reset' => $reset
			]
		]));

		if($response->code !== static::HTTP_OK)
		{
			throw new \UnexpectedValueException(
				'Unexpected HTTP response: '
					. PHP_EOL
					. stream_get_contents($response->code)
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

	protected function get($path, $content = NULL)
	{
		return $this->openRequest('GET', $path, $content);
	}

	protected function post($path, $content = NULL)
	{
		return $this->openRequest('POST', $path, $content);
	}

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
