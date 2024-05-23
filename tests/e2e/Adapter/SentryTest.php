<?php

namespace Utopia\Tests\E2E\Adapter;

use Utopia\Logger\Adapter\Sentry;
use Utopia\Tests\E2E\AdapterBase;

class SentryTest extends AdapterBase
{
    protected function setUp(): void
    {
        parent::setUp();
        var_dump(\getenv('TEST_SENTRY_DSN') . '  123');
        $this->adapter = new Sentry('projectId', 'key');
    }
}
