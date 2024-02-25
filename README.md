![avatar](https://avatars3.githubusercontent.com/u/640101?s=80&v=4)

# SeanMorris/Ksqlc

/*keɪ ɛs kyu ɛl si*/ • The Asynchronous PHP KSQL Connector

Ksqlc provides a PHP interface to Confluent KSQL & Apache Kafka.

Ksqlc is free for distribution, modification, and use under the [Apache-2.0 license](https://raw.githubusercontent.com/seanmorris/ksqlc/master/LICENSE).

[Docs](http://docs.ksqlc.seanmorr.is/) | [Github](http://github.com/seanmorris/ksqlc) | [Packagist](https://packagist.org/packages/seanmorris/ksqlc)

[![seanmorris/ksqlc](https://img.shields.io/packagist/v/seanmorris/ksqlc?color=900&label=seanmorris%2Fksqlc&logo=data%3Aimage%2Fpng%3Bbase64%2CiVBORw0KGgoAAAANSUhEUgAAACAAAAAfCAYAAACGVs%2BMAAAHb0lEQVR42pVWa1CU1xk2dSYzHSs4aEsEHZ0VAYEFBBGQAcRba5CxtUJpbeiIN2BdLgLLrizKQkx0ubhDMBCuy3UBuQuIGtAogSQ1pK026bSdtjO10TbRH06bJqPZp%2Bc5zLoyDJX9Zp7Z833f%2Bz7Pe97L%2BXbRPNdLtoVCoXBetWrVYwE4iMf0ncXpyOXm5pYsMLZ69eor%2Fv7%2BTwICAiB%2BFwTaKpXKJ8uXL7%2BydOnSMYHkBUjaI3R1dY3z8PC46%2BXlBW9vb4SGhiIsLMwqgAWA9tbw8HDs3bsX%2B%2FfvR0xMzF1BG7egbLi4uPiuWbPmEXeyZcsWK8U3b968YISEhCAwMJC%2BaGhowOjoqDUnJ4cbeUTu%2BXRtO1eyfhs3boRd2HEEBQXJAKqqqtDe3g6LxQKj0QhRTixZskQ5X%2Br3%2BPn53T9z5gx27txJEoeFufNDhw6hoqICZWVlaGxsRHNzM1paWvjLZ%2BS9v3jx4j2ztJ2cnBKjo6Ons7Ky0NHRgd27d5PMobSz8Tw9PaFWq9Hf389dS9Gmpia0traipqYGmZmZ0nbt2rXTosETn6Vg2bJlEwkJCRgZGbGazWZs376dhAsWZ7r37duHuLg45OfnU5zCNnBTzIqN08rGFgFMPAvA2dl5mN3a3d0NBhAbG4vg4GCSLyjtO3bs4G4pxHTPErdloLS09FlfcbpWrlw5PCsAZqCvr48OsnsTExPh6%2Bv7wmajjSifrHdbW9sccQbFrHAs6cMg7AHYu3%2BYZKxfZ2cnBgYGUFtbi5SUlOeb0XYe2MRlwxUWFsJgMDBzzMIc8cHBQWi1Wvj4%2BNCP%2FnMDcHd3HxaNgcjISOh0Ooni4mIcO3aMJxrTLMX9lP5Y77WBa7nzgoICClBoljADYUb0er1EUlISA54%2FAN7YTjyK2Y5dOnAkd%2B3ahZDNoQgO9EfoRhGQOCcio6JQVFTEjM0R5%2BzX19cjIiKCO7eJ%2F%2F8A%2BNCWXoJBsA9YVxL6Bgbj3PEDuNtWglfc3FFWXk5xe9PZA%2BAUsI94BPNQI5%2FjATADycnJaBdkASFhqNGr8O%2BxRmDKgvdrixETHQV3hSd%2B8csk0TP9FJfCGo2G5whHknxzJokjq%2FBYD2eXFfYAXv6eU%2F9axTq%2BtDWb%2BA1B1NZtSPhZIk4fScDD0Rrgd33AR53AdDeqNIdRfDQe6Un78fOkg1J8aGgIKpWKO2Qp54gT3qIkuyM24fCrUf1S%2FKYxM%2BhA7NbJ6LAQ%2BPj5zzSb2L2Xjy%2BUvj44uCdGCuI3vcAHlhlMtgO%2F7QP%2BcgUT1aexbUsI8vUFePPsWcTHx7Puc74lvCecV7iiIGkv%2FtBgmOzIPRi0qFOXjCfXxQwXZ8JV1JZRe673gMuKH0CVGAd80ounE214cquV4JoB2CDfPxiuhtc6BVxfWSn%2FjIhP%2BbynJjVaizLw9XgDqC0D%2BGLobeDXXRg5r5MEU7VFePqeGVPNpdDm5iAvO0siIz0dVW%2BeAj6%2BKDNhZQDMyFQ7vhypRl56KuIP%2FAq7Xt0Dl%2B%2B7InjTJvYBISfHzd0dVyv0wEdd%2BNelC%2FYAPu9%2FC%2Fj0Eu4N1%2BBHsXGoP3cKQ3XlaBC%2F53VqjDeU4mZTOSZbTLhWW4o642lmQgTdyZ6QgXS%2FfQ5TbZW40%2FMOJpvLYT6lgmLdOmzwVSJAlJSN90MR2P3LddSipj2Afw5eAO4MWL%2B81og3dNk4kpKGo0ePorIgC3%2B2lMwIfdIN3G7HZ0MXUPm6Xgb09Xst%2BEaUZby%2BBHnqVNEmJpEdi%2BDql1nSH0pAUIASCk9vbIvZKrhP4OG7ZqlFTVsA9%2F7eff6bbyda8ehyNcYrTiInMx0XCjLxJ4tRiF6cqfWHXZjqPofe5pNi3YnszAzcaCzDrWYTUlNSMWbSoa4yA7f7S2krxxWfXULuaz9GeEQkCk%2FmSe6HQoNa1KT2InGWf2fwdMr1Hv0R3CzPgfX9dug12bhaZxTp7bA323QvLEY1tGlx4t4Cgy4XqarjSBMoFGs2aJ54Rxva0kcGMdmG4eqz0OdJbqlBLWpSW45iU06Sf606MXK0OFV1y5SHdFWa8Kuw4k7fTKMRH%2Fegy5QBQ%2B4%2BmZX8vFwYTqRJ6LUaOapFmp%2FShrb0kYL4dEDso9KqFpzkpga1qCnF8dw%2F1K748O82Z79WWq5Vf2suOYXf91ZZMX2RPSAEevD55Qpcqy%2FASE0JjFpRgmoDwTWf8R1taCt96EsOcpGT3NSg1nPac29umDSW13NUX5kMOvxjpA5%2Fu1SDvw6%2Bgy%2BumnHbUikylIrL5Vp8NdZAcM1nfEcb2tJH%2BpKDXOScpfWi61p5blWD4QRSj6uhVkuImquRf0KN0TIN%2FnvdDHzYQXDNZ3xHG5u99CUHuRY5enVoDzbdMGnxcLzFyp0Q9wQeiDn%2Bj9i1rckIrvmM72hjs6cvOcjlcAD1xxM3XTdmdf2x5Q3OND9EVn4PCNaYjcjTk%2BCaz2zvaUsf%2BpKDXA5I22t0%2FvBPNoiRmbjXY3r6YOAtcXpVLAi0pQ99yfEcp%2BNBtKoPOIkD47EAHMRj%2Br5I%2FH8NrWvzntWI2gAAAABJRU5ErkJggg%3D%3D&style=for-the-badge)](http://finalbossoftheinternet.com/) [![Apache-2.0 Licence Badge](https://img.shields.io/github/license/seanmorris/ksqlc?color=338800&logo=apache&style=for-the-badge)](https://github.com/seanmorris/ksqlc/blob/master/LICENSE) [![CircleCI](https://img.shields.io/circleci/build/github/seanmorris/ksqlc?logo=circleci&logoColor=white&style=for-the-badge&token=b52ac489d3c2d170963021c81ecd422b7536f41c)](https://circleci.com/gh/seanmorris/ksqlc/) [![Size badge](https://img.shields.io/github/languages/code-size/seanmorris/ksqlc?logo=data%3Aimage%2Fpng%3Bbase64%2CiVBORw0KGgoAAAANSUhEUgAAABQAAAAOCAQAAACFzfR7AAABF0lEQVQoFQXBQWvOAQDA4ef/7o29YWtqKU7ExWE5OIvm4LKcnXwD7aQ0N/kAczO1i1KOO0xJvQojaTm4KbJabnJysLSf5wFAa603CUB322yOAAitVT86BTTQ1+oJDYDQcv+qFRr3vC1ooYPqDkHoYgfVKmnSfhG62t/qBkHn2q8ekjRpryB0v/rZ2eh4r6tpY5pp3Gx7RTONoJfVLnpQfekYtNG0832rRj3tEaT31bOxQ5wc/oATrnnniEMfXfaZDFrAoEk71XajNN9OVVW7HYVeVZ9AF/pd3YPm267qbYs0tF597wygpaquQ7Nt9QLoVlWXCEK3q1oCCF2p6iYBpKGN6kNzATrdr2qVAACa9rgRQKPetAnAf1jX/qSkN8aIAAAAAElFTkSuQmCC&style=for-the-badge)](https://github.com/seanmorris/ksqlc) [![Installs](https://img.shields.io/packagist/dt/seanmorris/ksqlc?color=338800&label=installs&logo=data:image/svg+xml;base64,PHN2ZyBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCAyOS45NzggMjkuOTc4IiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyOS45NzggMjkuOTc4IiB2ZXJzaW9uPSIxLjEiIHk9IjBweCIgeD0iMHB4Ij48ZyBmaWxsPSIjZmZmIj48cGF0aCBkPSJtMjUuNDYyIDE5LjEwNXY2Ljg0OGgtMjAuOTQ3di02Ljg0OGgtNC4wMjZ2OC44NjFjMCAxLjExMSAwLjkgMi4wMTIgMi4wMTYgMi4wMTJoMjQuOTY3YzEuMTE1IDAgMi4wMTYtMC45IDIuMDE2LTIuMDEydi04Ljg2MXoiLz48cGF0aCBkPSJtMTQuNjIgMTguNDI2LTUuNzY0LTYuOTY1cy0wLjg3Ny0wLjgyOCAwLjA3NC0wLjgyOGgzLjI0OHYtMS40MTYtOC43MjNzLTAuMTI5LTAuNDk0IDAuNjE1LTAuNDk0aDQuNTcyYzAuNTM2IDAgMC41MjQgMC40MTYgMC41MjQgMC40MTZ2OC43NDIgMS4yNjZoMi45OThjMS4xNTQgMCAwLjI4NSAwLjg2NyAwLjI4NSAwLjg2N3MtNC45MDQgNi41MS01LjU4OCA3LjE5M2MtMC40OTIgMC40OTUtMC45NjQtMC4wNTgtMC45NjQtMC4wNTh6Ii8+PC9nPjwvc3ZnPgo=&style=for-the-badge)](https://packagist.org/packages/seanmorris/ksqlc) [![Coverage](https://img.shields.io/codecov/c/github/seanmorris/ksqlc?logo=codecov&logoColor=white&style=for-the-badge)](https://codecov.io/gh/seanmorris/ksqlc) [![Min Supported PHP Version](https://img.shields.io/packagist/php-v/seanmorris/ksqlc?color=purple&logo=php&logoColor=white&style=for-the-badge)](https://app.circleci.com/pipelines/github/seanmorris/ksqlc?branch=master)

### Supports PHP 7.0 - PHP 8.3!

## Installation

Install via the [composer cli](https://getcomposer.org/doc/03-cli.md#require):

```sh

$ composer require seanmorris/ksqlc

```

... or add `seanmorris/ksqlc` to your [`composer.json`](https://getcomposer.org/doc/01-basic-usage.md#composer-json-project-setup):

```json
	"require": {
		"seanmorris/ksqlc": "dev-master"
	}
```

## Usage

#### Open a connection

Grab the URL to your KSQL server's REST endpoint, and it use it to create a new `Ksqlc` object to begin:

```php
<?php
use \SeanMorris\Ksqlc\Ksqlc;

$ksqlc = new Ksqlc('http://your-ksql-server:8088/');

```

### Ksqlc::stream() - Stream Queries Asynchronously

KSQLDB will push query results to you asynchronously when you're using `Ksqlc::stream()`.

Ksqlc will return streaming queries as generators. These can be iterated with `foreach`. Results will stream in until a limit is reached or the programmer breaks the loop  and destroys the reference.

```php
<?php
$stream = $ksqlc->stream('SELECT * FROM EVENT_STREAM EMIT CHANGES');

foreach($stream as $row)
{
	// $row == {"ROWKEY": "XXX", "ROWTIME": "YYY", ...}
	if($row->property === 'something')
	{
		break;
	}
}

unset($stream);

```

### Ksqlc::multiplex() - Stream Mutliple Queries

You can loop over multiple queries at once with `Ksqlc::multiplex()`. Each parameter to this method represents either a string query or a list of parameters to send to `Ksqlc::stream()`.

```php
<?php
$queryOne = 'SELECT * FROM EVENTS WHERE BODY = "AAA" EMIT CHANGES LIMIT 20';
$queryTwo = 'SELECT * FROM STREAM WHERE BODY = "BBB" EMIT CHANGES LIMIT 20';

$stream = $ksqlc->multiplex(
	[$queryOne, 'earliest'],
	[$queryTwo, 'earliest']
);

foreach($stream as $row) { /* Stream processing... */ }
```

#### Limits

Queries with limits will terminate when the given number of rows have been iterated.

Multiplexed queries will terminate when all limits have been reached.

```php
<?php
$stream = $ksqlc->stream('SELECT * FROM EVENT_STREAM EMIT CHANGES LIMIT 20');

foreach($stream as $row)
{
	/* Stream processing... */
}
```

#### Offset Reset

Streaming queries will **ONLY** select new records by default. Use the second param to `Ksqlc::stream()` to process all records from the beginning of time.

```php
<?php
$stream = $ksqlc->stream($queryString, 'earliest'); ## process everything
$stream = $ksqlc->stream($queryString, 'latest');   ## process new records
```

#### Full asyncronicity

Passing `TRUE` to the third parameter of `Ksqlc::stream()` allows you to turn on full syncronous mode.

In this example, the foreach loop will spin indefinitely until the query returns 20 records and completes. If there is no data to process, a stream of `NULL`'s will be supplied. This allows you to tend to other, unrelated streams in the same loop, or even break the loop and resume processing later on.

```php
<?php
$query  = 'SELECT * FROM EVENT_STREAM EMIT CHANGES LIMIT 20';
$stream = $ksqlc->stream($queryString, 'latest', TRUE);

foreach($stream as $row)
{
	var_dump($row);
}
```

### Ksqlc::run() - Run a KSQL statment

You'll do things like create or drop tables and streams with this method. Any statement that isnt a direct `SELECT` should be passed to `Ksqlc::run()`.

Ksqlc::run will return an iterable object of results with metadata properties:

```php
<?php
$results = $ksqlc->run('SHOW TABLES');
var_dump( $results );
// object SeanMorris\Ksqlc\Result {
//  $type          => "tables"
//  $warnings      => {}
//  $statementText => "SHOW TABLES"
// }

foreach($results as $table)
{
	var_dump( $table );
	// object stdClass {
	// 	$type       => "TABLE"
	// 	$name       => "event_table"
	// 	$topic      => "event_table"
	// 	$format     => "JSON"
	// 	$isWindowed => false
	// }
}
```

You can also use list destructuring to get the results of multiple queries all at once:

```php
<?php
[$streams, $tables] = $ksqlc->run('SHOW STREAMS', 'SHOW TABLES');

foreach($streams as $streams)
{
	// ...
}

foreach($tables as $table)
{
	// ...
}

```

### SeanMorris/Ksqlc

Copyright 2020 - 2024 Sean Morris

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

* http://www.apache.org/licenses/LICENSE-2.0
* https://raw.githubusercontent.com/seanmorris/ksqlc/master/LICENSE

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
