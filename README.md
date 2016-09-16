# bittyphp/env

[![Build Status](https://travis-ci.org/bittyphp/env.svg?branch=master)](https://travis-ci.org/bittyphp/env)
[![Coverage Status](https://coveralls.io/repos/github/bittyphp/env/badge.svg)](https://coveralls.io/github/bittyphp/env)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://github.com/bittyphp/env/blob/master/LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/bittyphp/env.svg?style=flat-square)](https://packagist.org/packages/bittyphp/env)

Set and use environment from JSON file.
Inspired [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv).  
:warning: **JSON FILE ONLY**  

Support PHP >= 5.3

## Install

Using Composer

```php
composer.phar require bittyphp/env
```

## SetUp

```php
<?php
// Autoload only
require_once '/path/to/vendor/autoload.php';
```


## Function usage

JSON example

```json
{
    "FOO": "Foo value",
    "BAR": {
        "BAR-one": "Bar One value",
        "BAR-two": "Bar Two value"
    }
}
```

Usage

```php
<?php
// Load JSON file
env(array('/path/to/example.json'));

// Or multiple files
// env(array('/path/to/example.json', '../../other.json'));

// Basic use
echo env('FOO'); // "Foo value"

// You can use dot separated name
// (better than vlucas/phpdotenv)
echo env('BAR.BAR-one'); // "Bar One value"

// Get all environments
$all = env();

// If you need clear all environment, set PHP_EOL
env(PHP_EOL);
```


## JSON format

Simple

```json
{
    "FOO": "Foo value",
    "BAR": "Bar value"
}
```

Nested

```json
{
    "FOO": {
        "FOO-one": "Foo One value",
        "FOO-two": "Foo Two value"
    },
    "BAR": [
        "Bar One value",
        "Bar Two value"
    ]
}
```

Placeholder

```json
{
    "FOO": "Foo value",
    "BAR": "{FOO} after Bar value"
}
```

And reverse replacing placeholder  
(better than [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv))

```json
{
    "FOO": "{BAR} before Foo value",
    "BAR": "Bar value"
}
```

Nested placeholder  
(better than [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv))

```json
{
    "FOO": {
        "FOO-one": "Foo One value",
        "FOO-two": "Foo Two value"
    },
    "BAR": "{FOO.FOO-two} and Bar value"
}
```


## Class version

Function `env()` is alias of this class.

```php
<?php
use \BittyPHP\Env;

// Load JSON file
Env::file('/path/to/env.json');

// Or multiple files
Env::file(array('/path/to/env.json', '../other.json'));
Env::file('/path/to/env.json', '../other.json');

// Basic use
echo Env::get('FOO');

// You can use dot separated name
echo Env::get('BAR.BAR-one');

// Get all environments
$all = Env::all();

// Clear all environment
 Env::clear();
```


## TODO

JavaScript(node.js) version
