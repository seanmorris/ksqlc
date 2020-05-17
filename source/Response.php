<?php
namespace SeanMorris\Ksqlc;

use \IteratorAggregate, \ArrayIterator;

/**
 * Represents a generic KSQL response.
 */
trait Response
{
	/**
	 * Return an iterator for the response body.
	 */
	public function getIterator()
	{
		$result = [];

		if(isset($this->blob[ $this->type ]))
		{
			$result = $this->blob[ $this->type ];
		}

		return new ArrayIterator($result);
	}
}