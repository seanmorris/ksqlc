<?php
namespace SeanMorris\Ksqlc;

use \Iterator, \MultipleIterator, \NoRewindIterator;

class StreamIterator extends MultipleIterator
{
	protected $current = [];

	public function __construct()
	{
		parent::__construct(MultipleIterator::MIT_NEED_ANY);
	}

	public function attachIterator(Iterator $iterator, $info = NULL)
	{
		parent::attachIterator(new NoRewindIterator($iterator), $info);
	}

	public function current()
	{
		if(!$this->current)
		{
			$this->current = parent::current();
		}

		return array_shift($this->current);
	}

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
