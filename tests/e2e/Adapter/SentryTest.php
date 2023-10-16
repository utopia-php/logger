<?php

namespace Utopia\Tests\E2E\Adapter;

use Utopia\Logger\Adapter\Sentry;
use Utopia\Tests\E2E\AdapterBase;

class SentryTest extends AdapterBase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new Sentry(\getenv('TEST_SENTRY_DSN') ?: '');
    }
}
