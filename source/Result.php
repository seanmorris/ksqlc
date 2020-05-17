<?php
namespace SeanMorris\Ksqlc;

use \IteratorAggregate;

/**
 * Represents a KSQL statement result.
 */
class Result implements IteratorAggregate
{
	protected $type, $statementText, $warnings;

	use Ingestor;
	use Response;	
}
