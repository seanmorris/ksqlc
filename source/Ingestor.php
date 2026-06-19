<?php
namespace SeanMorris\Ksqlc;

use \InvalidArgumentException;

/**
 * Data structure ingestion behavior.
 */
trait Ingestor
{
	protected $type, $blob;

	/**
	 * Ingest a data structure.
	 *
	 * @param array/object $blob Data to ingest.
	 */
	public function ingest($blob)
	{
		$blob = (object) $blob;

		if(!isset($blob->{ '@type' }))
		{
			throw new \InvalidArgumentException('Blob does not specify a @type.');
		}

		$this->type = $blob->{ '@type' };

		if(isset($blob->commandStatus))
		{
			$this->message = $blob->commandStatus->message;
			$this->status  = $blob->commandStatus->status;
		}

		if($this->blob)
		{
			throw new \BadMethodCallException('Cannot ingest twice.');
		}

		$this->blob = $blob;

		foreach($blob as $prop => $original)
		{
			if(!property_exists(get_called_class(), $prop))
			{
				continue;
			}

			$this->{ $prop } = $blob->{ $prop };
		}
	}

	/**
	 * Allow readonly access to protected keys.
	 *
	 * @param string $name The property being read.
	 *
	 * @return mixed The value of the property.
	 */
	public function __get($name)
	{
		return $this->{ $name };
	}
}
