<?php
namespace SeanMorris\Ksqlc\Test;

class FakeHttp
{
	public static $requests = [];
	public static $responses = [];

	public static function get($url, $content = NULL, $headers = [])
	{
		return static::openRequest('GET', $url, $content, $headers);
	}

	public static function post($url, $content = NULL, $headers = [])
	{
		return static::openRequest('POST', $url, $content, $headers);
	}

	public static function openRequest($method, $url, $content = NULL, $headers = [])
	{
		static::$requests[] = (object) [
			'method'  => $method
			, 'url'   => $url
			, 'content' => $content
			, 'headers' => $headers
		];

		$response = static::$responses
			? array_shift(static::$responses)
			: [];

		if(is_string($response))
		{
			$response = ['body' => $response];
		}

		$response = (object) $response;

		$stream = $response->stream ?? fopen('php://memory', 'r+');

		if(isset($response->body))
		{
			fwrite($stream, $response->body);
			rewind($stream);
		}

		$code = $response->code ?? 0;

		return (object) [
			'http'     => 0
			, 'code'   => $code
			, 'status' => $response->status ?? ($code . ' ERROR')
			, 'header' => (object) []
			, 'stream' => $stream
		];
	}
}
