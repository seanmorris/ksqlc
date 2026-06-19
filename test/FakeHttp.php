<?php
namespace SeanMorris\Ksqlc\Test;

class FakeHttp
{
	public static $requests = [];

	public static function get($url, $content = NULL)
	{
		return static::openRequest('GET', $url, $content);
	}

	public static function post($url, $content = NULL)
	{
		return static::openRequest('POST', $url, $content);
	}

	public static function openRequest($method, $url, $content = NULL)
	{
		static::$requests[] = (object) [
			'method'  => $method
			, 'url'   => $url
			, 'content' => $content
		];

		return (object) [
			'http'     => 0
			, 'code'   => 0
			, 'status' => '0 ERROR'
			, 'header' => (object) []
			, 'stream' => fopen('php://memory', 'r')
		];
	}
}
