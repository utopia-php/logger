<?php

namespace Utopia\Tests\Unit;

use Exception;
use PHPUnit\Framework\TestCase;
use Utopia\Logger\Adapter;
use Utopia\Logger\Log;
use Utopia\Logger\Logger;

class LoggerTest extends TestCase
{
    protected function getAdapter(): Adapter
    {
        return new class extends Adapter
        {
            public static function getName(): string
            {
                return 'mock';
            }

            public function push(Log $log): int
            {
                return 200;
            }

            public function getSupportedTypes(): array
            {
                return [Log::TYPE_DEBUG, Log::TYPE_ERROR, Log::TYPE_WARNING, Log::TYPE_INFO, Log::TYPE_VERBOSE];
            }

            public function getSupportedEnvironments(): array
            {
                return [Log::ENVIRONMENT_PRODUCTION, Log::ENVIRONMENT_STAGING];
            }

            public function getSupportedBreadcrumbTypes(): array
            {
                return [Log::TYPE_DEBUG, Log::TYPE_ERROR, Log::TYPE_WARNING, Log::TYPE_INFO, Log::TYPE_VERBOSE];
            }
        };
    }

    /**
     * A log which was never populated must be rejected with an Exception,
     * not a fatal Error about uninitialized typed properties.
     *
     * @throws Exception
     */
    public function testAddLogWithEmptyLogThrowsException(): void
    {
        $logger = new Logger($this->getAdapter());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Log is not ready to be pushed.');

        $logger->addLog(new Log());
    }

    /**
     * A partially populated log (missing the action) must also be rejected
     * with an Exception.
     *
     * @throws Exception
     */
    public function testAddLogWithMissingActionThrowsException(): void
    {
        $logger = new Logger($this->getAdapter());

        $log = new Log();
        $log->setType(Log::TYPE_ERROR);
        $log->setMessage('Something went wrong');
        $log->setVersion('1.0.0');
        $log->setEnvironment(Log::ENVIRONMENT_PRODUCTION);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Log is not ready to be pushed.');

        $logger->addLog($log);
    }

    /**
     * Reading a required field of a fresh log must not fatal.
     */
    public function testUnsetRequiredFieldsAreEmptyStrings(): void
    {
        $log = new Log();

        self::assertSame('', $log->getAction());
        self::assertSame('', $log->getType());
        self::assertSame('', $log->getMessage());
        self::assertSame('', $log->getVersion());
        self::assertSame('', $log->getEnvironment());
    }

    /**
     * A fully populated log is pushed to the adapter.
     *
     * @throws Exception
     */
    public function testAddLogWithCompleteLog(): void
    {
        $logger = new Logger($this->getAdapter());

        $log = new Log();
        $log->setType(Log::TYPE_ERROR);
        $log->setMessage('Something went wrong');
        $log->setVersion('1.0.0');
        $log->setEnvironment(Log::ENVIRONMENT_PRODUCTION);
        $log->setAction('testAction');

        self::assertEquals(200, $logger->addLog($log));
    }
}
