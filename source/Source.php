<?php
namespace SeanMorris\Ksqlc;

/**
 * Represents a KSQL table or stream.
 */
class Source extends Result
{
	const SINGULAR = TRUE;

	use Ingestor;
	use Response;

	protected
		$name
		, $type
		, $windowType
		, $key
		, $timestamp
		, $statistics
		, $errorStats
		, $extended
		, $keyFormat
		, $valueFormat
		, $topic
		, $partitions
		, $replication
		, $statement
		, $fields       = []
		, $readQueries  = []
		, $writeQueries = [];

	public function ingest($blob)
	{
		parent::ingest($blob);

		foreach($blob->sourceDescription as $prop => $original)
		{
			if(!property_exists(get_called_class(), $prop))
			{
				continue;
			}

			$this->{ $prop } = $blob->sourceDescription->{ $prop };
		}
	}
}
