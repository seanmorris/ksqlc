<?php
namespace SeanMorris\Ksqlc;

/**
 * Provides basic streaming HTTP.
 */
class Http
{
	/**
	 * Issue an HTTP GET request.
	 * 
	 * @param string $path The path to request
	 * @param object $content raw data to include with request
	 * 
	 * @return object An object detailing the HTTP headers, with a readable STREAM containing the actual response body.
	 */
	public static function get($url, $content = NULL)
	{
		return static::openRequest('GET', $url);
	}

	/**
	 * Issue an HTTP POST request to the KSQLDB endpoint.
	 * 
	 * @param string $path The path to request
	 * @param object $content raw data to include with request
	 * 
	 * @return object An object detailing the HTTP headers, with a readable STREAM containing the actual response body.
	 */
	public static function post($url, $content = NULL)
	{
		return static::openRequest('POST', $url, $content);
	}

	/**
	 * Issue an HTTP request.
	 * 
	 * Returns an object with the following properties:
	 * 
	 * ->http   - "HTTP 1.0", "HTTP 1.1" or "HTTP 2"
	 * ->code   - The HTTP response code 
	 * ->status - The HTTP status line.
	 * ->header - Associative array of headers
	 * ->stream - Stream resource containing response body
	 * 
	 * @param string $method The HTTP method to use.
	 * @param string $path The path to request
	 * @param object $content raw data to include with request
	 * 
	 * @return object An object detailing the HTTP headers, with a readable stream resource containing the actual response body.
	 */
	public static function openRequest($method, $url, $content = NULL)
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

		$handle = fopen($url, 'r', FALSE, $context);

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
			, 'status' => '0 ERROR'
			, 'header' => (object) []
			, 'stream' => $handle
		]);
	}
}