<?php
namespace SeanMorris\Eventi\Ksql;

/**
 * Represents a KSQL status message.
 */
class Status
{
	protected $status, $message, $command, $error_code, $statementText;
	use Ingestor;
	use Response;
}
