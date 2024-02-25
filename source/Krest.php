<?php
namespace SeanMorris\Ksqlc;

/**
 * Provides an interface to Kafka's REST Proxy from PHP.
 */
class Krest
{
	protected $endpoint;
	protected static $Http;

	use Injectable;

	/**
	 * Return a new connection to the Kafka Rest API.
	 *
	 * @param string $endpoint The URL to Kafka's REST endpoint.
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

	public function topics()
	{
		$response = static::$Http::get(
			$this->endpoint . '/topics'
			, NULL
			, ['Connection: close']
		);

		if($response->code !== HTTP::STATUS_OK)
		{
			throw new \UnexpectedValueException(
				'Unexpected HTTP response: '
					. PHP_EOL
					. stream_get_contents($response->stream)
				, $response->code
			);
		}

		$raw = stream_get_contents($response->stream);

		if(!$result = json_decode($raw))
		{
			throw new \UnexpectedValueException(
				'Unexpected formatting in HTTP response.'
			);
		}

		return $result;
	}

	/**
	 * Send messages to a topic.
	 *
	 * @param string $topic The name of the topic.
	 * @param array $records The records to send.
	 */
	public function produce($topic, ...$records)
	{
		foreach($records as &$record)
		{
			$record = ['value' => $record];
		}

		$response = static::$Http::post(
			$this->endpoint . '/topics/' . $topic
			, json_encode(['records' => $records])
		);

		if($response->code !== HTTP::STATUS_OK)
		{
			throw new \UnexpectedValueException(
				'Unexpected HTTP response: '
					. PHP_EOL
					. stream_get_contents($response->stream)
				, $response->code
			);
		}

		return $response;
	}
}

Krest::inject(['Http' => Http::class]);
