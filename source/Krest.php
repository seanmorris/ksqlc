<?php
namespace SeanMorris\Ksqlc;

/**
 * Provides an interface to Kafka's REST Proxy from PHP.
 */
class Krest
{
	protected $endpoint;
	protected const HTTP_OK = 200;
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

	/**
	 * Send messages to a topic.
	 *
	 * @param string $topic The name of the topic.
	 * @param array $records The records to send.
	 */
	public function produce($topic, ...$records)
	{
		$response = static::$Http::post(
			$this->endpoint . '/topics/' . $topic
			, json_encode(['records' => $records]
		));
	}
}

Krest::inject(['Http' => Http::class]);
