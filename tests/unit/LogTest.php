<?php

namespace Utopia\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Utopia\Logger\Log;
use Utopia\Logger\Log\Breadcrumb;
use Utopia\Logger\Log\User;

class LogTest extends TestCase
{
    /**
     * @throws \Throwable
     */
    public function testLog(): void
    {
        $log = new Log();

        $timestamp = \microtime(true);
        $log->setTimestamp($timestamp);
        self::assertSame($timestamp, $log->getTimestamp());

        $log->setType(Log::TYPE_ERROR);
        self::assertSame(Log::TYPE_ERROR, $log->getType());
        $log->setType(Log::TYPE_DEBUG);
        self::assertSame(Log::TYPE_DEBUG, $log->getType());
        $log->setType(Log::TYPE_WARNING);
        self::assertSame(Log::TYPE_WARNING, $log->getType());
        $log->setType(Log::TYPE_VERBOSE);
        self::assertSame(Log::TYPE_VERBOSE, $log->getType());
        $log->setType(Log::TYPE_INFO);
        self::assertSame(Log::TYPE_INFO, $log->getType());

        $log->setMessage("Cannot read 'user' of undefined");
        self::assertSame("Cannot read 'user' of undefined", $log->getMessage());

        $log->setVersion('0.11.0');
        self::assertSame('0.11.0', $log->getVersion());

        $log->setEnvironment(Log::ENVIRONMENT_PRODUCTION);
        self::assertSame(Log::ENVIRONMENT_PRODUCTION, $log->getEnvironment());
        $log->setEnvironment(Log::ENVIRONMENT_STAGING);
        self::assertSame(Log::ENVIRONMENT_STAGING, $log->getEnvironment());

        $log->setNamespace('getAuthUser');
        self::assertSame('getAuthUser', $log->getNamespace());

        $log->setAction('authGuard');
        self::assertSame('authGuard', $log->getAction());

        $log->setServer('aws-001');
        self::assertSame('aws-001', $log->getServer());

        $log->addExtra('isLoggedIn', false);
        self::assertSame(['isLoggedIn' => false], $log->getExtra());

        $log->addTag('authMethod', 'session');
        $log->addTag('authProvider', 'basic');
        self::assertSame(['authMethod' => 'session', 'authProvider' => 'basic'], $log->getTags());

        $userId = 'myid123';
        $user = new User($userId);
        $log->setUser($user);
        self::assertSame($user, $log->getUser());
        self::assertSame($userId, $log->getUser()?->getId());

        $breadcrumb = new Breadcrumb(Log::TYPE_DEBUG, 'http', 'DELETE /api/v1/database/abcd1234/efgh5678', $timestamp);
        $log->addBreadcrumb($breadcrumb);
        self::assertSame([$breadcrumb], $log->getBreadcrumbs());
        self::assertSame(Log::TYPE_DEBUG, $log->getBreadcrumbs()[0]->getType());
        self::assertSame('http', $log->getBreadcrumbs()[0]->getCategory());
        self::assertSame('DELETE /api/v1/database/abcd1234/efgh5678', $log->getBreadcrumbs()[0]->getMessage());
        self::assertSame($timestamp, $log->getBreadcrumbs()[0]->getTimestamp());
    }

    public function testLogMasked(): void
    {
        $log = new Log();

        $log->addTag('password', '123456');
        $log->addExtra('name', 'John Doe');

        self::assertSame(['password' => '123456'], $log->getTags());
        self::assertSame(['name' => 'John Doe'], $log->getExtra());

        $log->setMasked(['password', 'name']);

        self::assertSame(['password' => '******'], $log->getTags());
        self::assertSame(['name' => '********'], $log->getExtra());

        // test nested array
        $log->addExtra('user', ['password' => 'abc']);

        self::assertSame(['password' => '***'], $log->getExtra()['user']);

        // test remove mask
        $log->setMasked([]);

        self::assertSame(['password' => '123456'], $log->getTags());
        self::assertSame(['name' => 'John Doe', 'user' => ['password' => 'abc']], $log->getExtra());
    }
}
