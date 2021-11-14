# Utopia Logging

[![Build Status](https://travis-ci.org/utopia-php/logging.svg?branch=main)](https://travis-ci.com/utopia-php/logging)
![Total Downloads](https://img.shields.io/packagist/dt/utopia-php/logging.svg)
[![Discord](https://img.shields.io/discord/564160730845151244)](https://appwrite.io/discord)

Utopia Logging library is simple and lite library for logging information, such as errors or warnings. This library aims to be as simple and easy to learn and use as possible. This library is maintained by the [Appwrite team](https://appwrite.io).

Although the library was built for the [Utopia Framework](https://github.com/utopia-php/framework) project, it is completely independent, **dependency-free** and can be used with any other PHP project or framework.

## Getting Started

Install using composer:
```bash
composer require utopia-php/logging
```

```php
<?php

require_once '../vendor/autoload.php';

use Utopia\Logging\Adapter\AppSignal;
use Utopia\Logging\Adapter\Raygun;
use Utopia\Logging\Adapter\Sentry;
use Utopia\Logging\Log;
use Utopia\Logging\Log\Breadcrumb;
use Utopia\Logging\Log\User;
use Utopia\Logging\Logging;

// Prepare log
$log = new Log();
$log->setAction("controller.database.deleteDocument");
$log->setEnvironment("production");
$log->setLogger("api");
$log->setServer("digitalocean-us-001");
$log->setType("warning");
$log->setVersion("0.11.5");
$log->setMessage("Document efgh5678 not found");
$log->setUser(new User("efgh5678"));
$log->setBreadcrumbs([
    new Breadcrumb("debug", "http", "DELETE /api/v1/database/abcd1234/efgh5678", \microtime(true) - 500),
    new Breadcrumb("debug", "auth", "Using API key", \microtime(true) - 400),
    new Breadcrumb("info", "auth", "Authenticated with * Using API Key", \microtime(true) - 350),
    new Breadcrumb("info", "database", "Found collection abcd1234", \microtime(true) - 300),
    new Breadcrumb("debug", "database", "Permission for collection abcd1234 met", \microtime(true) - 200),
    new Breadcrumb("error", "database", "Missing document when searching by ID!", \microtime(true)),
]);
$log->setTags([
    'sdk' => 'Flutter',
    'sdkVersion' => '0.0.1',
    'authMode' => 'default',
    'authMethod' => 'cookie',
    'authProvider' => 'MagicLink'
]);
$log->setExtra([
    'urgent' => false,
    'isExpected' => true
]);

// Sentry
$adapter = new Sentry("[YOUR_SENTRY_KEY]", \getenv("[YOUR_SENTRY_PROJECT_ID]"));
$logging = new Logging($adapter);
$logging->addLog($log);

// AppSignal
$adapter = new AppSignal(\getenv("[YOUR_APPSIGNAL_KEY]"));
$logging = new Logging($adapter);
$logging->addLog($log);

// Raygun
$adapter = new Raygun(\getenv("[YOUR_RAYGUN_KEY]"));
$logging = new Logging($adapter);
$logging->addLog($log);

```

### Adapters

Below is a list of supported adapters, and thier compatibly tested versions alongside a list of supported features and relevant limits.

| Adapter | Status |
|---------|---------|
| Sentry | ✅ |
| AppSignal | ✅ |
| Raygun | 🛠 |

` ✅  - supported, 🛠  - work in progress`

## Tests

To run all unit tests, use the following Docker command:

```bash
docker run --rm -e TEST_RAYGUN_KEY=KKKK -e TEST_APPSIGNAL_KEY=XXXX -e TEST_SENTRY_KEY=YYYY -e TEST_SENTRY_PROJECT_ID=ZZZZ -v $(pwd):$(pwd):rw -w $(pwd) php:7.4-cli-alpine sh -c "vendor/bin/phpunit --configuration phpunit.xml tests"
```

> Make sure to replace `TEST_SENTRY_KEY` and `TEST_SENTRY_PROJECT_ID` environment variables value with actual keys from Sentry. If your Sentry DSN is `https://something@otherthing.ingest.sentry.io/anything`, then `TEST_SENTRY_KEY=something` and `TEST_SENTRY_PROJECT_ID=anything`

> Make sure to replace `TEST_APPSIGNAL_KEY` with key found in Appsignal -> Project -> App Settings -> Push & deploy -> Push Key

> Make sure to replace `TEST_RAYGUN_KEY` with key found in Raygun -> Project -> Application Settings -> Api Key

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
