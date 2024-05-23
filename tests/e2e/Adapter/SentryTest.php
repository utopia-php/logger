<?php

namespace Utopia\Tests\E2E\Adapter;

use Utopia\Logger\Adapter\Sentry;
use Utopia\Tests\E2E\AdapterBase;

class SentryTest extends AdapterBase
{
    protected function setUp(): void
    {
        parent::setUp();
        $dsn = \getenv('TEST_SENTRY_DSN');
        $parsed = parse_url($dsn ?? '');
        $host = $parsed['host'] ?? '';
        $path = $parsed['path'] ?? '';
        $user = $parsed['user'] ?? '';
        $scheme = $parsed['scheme'] ?? '';

        $url = $scheme.'://'.$host;

        var_dump($url, $user, $path);
        $this->adapter = new Sentry($path, $user, $url);
    }
}
