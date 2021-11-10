# Utopia Logging

[![Build Status](https://travis-ci.org/utopia-php/logging.svg?branch=main)](https://travis-ci.com/utopia-php/logging)
![Total Downloads](https://img.shields.io/packagist/dt/utopia-php/logging.svg)
[![Discord](https://img.shields.io/discord/564160730845151244)](https://appwrite.io/discord)

Utopia Logging library is simple and lite library for logging information, such as errors or warnings. This library is aiming to be as simple and easy to learn and use. This library is maintained by the [Appwrite team](https://appwrite.io).

Although this library is part of the [Utopia Framework](https://github.com/utopia-php/framework) project, it is completely **dependency-free** and can be used as standalone with any other PHP project or framework.

## Getting Started

Install using composer:
```bash
composer require utopia-php/logging
```

```php
<?php

require_once '../vendor/autoload.php';

use blabla;

blabla
```

## Library API

* **blabla()** - bla bla

> bla bla
> 
>
### Adapters

Below is a list of supported adapters, and thier compatibly tested versions alongside a list of supported features and relevant limits.

| Adapter | Status |
|---------|---------|
| Sentry | 🛠 |
| AppSignal | 🛠 |
| Raygun | 🛠 |

` ✅  - supported, 🛠  - work in progress`

## Tests

To run all unit tests, use the following Docker command:

```bash
docker run --rm -e TEST_SENTRY_KEY=YYYY -e TEST_SENTRY_PROJECT_ID=ZZZZ -v $(pwd):$(pwd):rw -w $(pwd) php:7.4-cli-alpine sh -c "vendor/bin/phpunit --configuration phpunit.xml tests"
```

> Make sure to replace `TEST_SENTRY_KEY` and `TEST_SENTRY_PROJECT_ID` environment variables value with actual keys from Sentry. If your Sentry DSN is `https://something@otherthing.ingest.sentry.io/anything`, then `TEST_SENTRY_KEY=something` and `TEST_SENTRY_PROJECT_ID=anything`

To run static code analysis, use the following Psalm command:

```bash
docker run --rm -v $(pwd):$(pwd):rw -w $(pwd) php:7.4-cli-alpine sh -c "vendor/bin/psalm --show-info=true"
```

## System Requirements

Utopia Framework requires PHP 7.4 or later. We recommend using the latest PHP version whenever possible.

## Authors

**Eldad Fux**

+ [https://twitter.com/eldadfux](https://twitter.com/eldadfux)
+ [https://github.com/eldadfux](https://github.com/eldadfux)

**Matej Bačo**

+ [https://github.com/Meldiron](https://github.com/Meldiron)

**Christy Jacob**

+ [https://github.com/christyjacob4](https://github.com/christyjacob4)

## Copyright and license

The MIT License (MIT) [http://www.opensource.org/licenses/mit-license.php](http://www.opensource.org/licenses/mit-license.php)
