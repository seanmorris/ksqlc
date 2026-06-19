<?php

$timeout = getenv('WAIT_SECONDS') ?: getenv('SECONDS') ?: 120;
$deadline = time() + (int) $timeout;

$services = [
	'ksql-server'  => 'http://ksql-server:8088/info',
	'krest-server' => 'http://krest-server:8082/topics',
];

while($services)
{
	foreach($services as $name => $url)
	{
		if(@file_get_contents($url) !== false)
		{
			fwrite(STDERR, sprintf("%s is ready.\n", $name));
			unset($services[$name]);
		}
	}

	if(!$services)
	{
		exit(0);
	}

	if(time() >= $deadline)
	{
		fwrite(STDERR, sprintf(
			"Timed out waiting for: %s\n",
			implode(', ', array_keys($services))
		));

		exit(1);
	}

	sleep(2);
}
