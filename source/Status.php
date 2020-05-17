<?php
namespace SeanMorris\Ksqlc;

/**
 * Represents a KSQL status message.
 */
class Status
{
	protected $status, $message, $warnings, $commandId, $error_code, $statementText;
	use Ingestor;
	use Response;
}
