<?php

namespace Utopia\Tests\E2E\Adapter;

use Utopia\Logger\Adapter\Datadog;
use Utopia\Tests\E2E\AdapterBase;

class DatadogTest extends AdapterBase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new Datadog(\getenv('TEST_DATADOG_KEY') ?: '');
    }
}
