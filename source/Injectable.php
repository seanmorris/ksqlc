<?php
namespace SeanMorris\Ksqlc;

/**
 * Dependency injection behavior.
 */
trait Injectable
{
	/**
	 * Inject class dependencies at runtime.
	 */
	public static function inject($injections)
	{
		foreach($injections as $key => $class)
		{
			static::$$key = $class;
		}
	}
}