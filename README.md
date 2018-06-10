# doctrine-psr-sql-logger
PSR-3 Compliant Doctrine SQL Logger

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/abacaphiliac/doctrine-psr-sql-logger/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/abacaphiliac/doctrine-psr-sql-logger/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/abacaphiliac/doctrine-psr-sql-logger/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/abacaphiliac/doctrine-psr-sql-logger/?branch=master)
[![Build Status](https://travis-ci.org/abacaphiliac/doctrine-psr-sql-logger.svg?branch=master)](https://travis-ci.org/abacaphiliac/doctrine-psr-sql-logger)

## Installation
```bash
composer require abacaphiliac/doctrine-psr-sql-logger
```

## Usage

The following configuration snippet will log the query with its parameter types and execution duration.
In general, this will be safe to use with parameterized queries, since values will not be printed to the log stream.
```php
$logger = new \Psr\Log\NullLogger(); // Get your real logger(s) from a container.
$configuration = new \Doctrine\DBAL\Configuration();
$configuration->setSQLLogger(new \Abacaphiliac\Doctrine\PsrSqlLogger($logger));
```

The following snippet will additionally log parameter values. Be careful to handle sensitive data appropriately. 
```php
$logger = new \Psr\Log\NullLogger(); // Get your real logger(s) from a container.
$configuration = new \Doctrine\DBAL\Configuration();
$configuration->setSQLLogger(new \Abacaphiliac\Doctrine\PsrSqlParamsLogger($logger));
```

## Contributing
```
composer update && composer build
```
