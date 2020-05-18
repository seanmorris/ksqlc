<?php
namespace SeanMorris\Ksqlc;

use \Generator, \Iterator, \MultipleIterator, \NoRewindIterator;

/**
 * Serialize multiple streams into a single iterator via interpolation.
 *
 * Handles async streams.
 */
class StreamIterator extends MultipleIterator
{
	protected $current = [];

	/**
	 * Return a new StreamIterator.
	 */
	public function __construct()
	{
		parent::__construct(MultipleIterator::MIT_NEED_ANY);
	}

	/**
	 * Attach a new stream generator.
	 */
	public function attachIterator(Generator $iterator, $info = NULL)
	{
		parent::attachIterator(new NoRewindIterator($iterator), $info);
	}

	/**
	 * Return the data from the current stream.
	 */
	public function current()
	{
		if(!$this->current)
		{
			$this->current = parent::current();
		}

		return array_shift($this->current);
	}

	/**
	 * Move to the next stream, or next overall iteration.
	 */
	public function next()
	{
		if(!$this->current)
		{
			$nextChunk = parent::next();

			if($nextChunk === FALSE)
			{
				return FALSE;
			}

			$this->current = $nextChunk;
		}

		return TRUE;
	}
}
