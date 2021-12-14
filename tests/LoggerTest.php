<?php
/**
 * Utopia PHP Framework
 *
 * @package Logger
 * @subpackage Tests
 *
 * @link https://github.com/utopia-php/framework
 * @author Eldad Fux <eldad@appwrite.io>
 * @version 1.0 RC4
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

use PHPUnit\Framework\TestCase;

use Utopia\Logger\Adapter\AppSignal;
use Utopia\Logger\Adapter\Raygun;
use Utopia\Logger\Adapter\Sentry;
use Utopia\Logger\Log;
use Utopia\Logger\Log\Breadcrumb;
use Utopia\Logger\Log\User;
use Utopia\Logger\Logger;

class LoggerTest extends TestCase
{
    public function testLogUser()
    {
        $user = new User();

        self::assertEquals(null, $user->getEmail());
        self::assertEquals(null, $user->getUsername());
        self::assertEquals(null, $user->getId());

        $user = new User("618e291cd8949");
        self::assertEquals("618e291cd8949", $user->getId());

        $user = new User(null, "matej@appwrite.io");
        self::assertEquals("matej@appwrite.io", $user->getEmail());

        $user = new User(null, null, "Meldiron");
        self::assertEquals("Meldiron", $user->getUsername());
    }

    /**
     * @throws Exception
     */
    public function testLog()
    {
        $log = new Log();

        $timestamp = \microtime(true);
        $log->setTimestamp($timestamp);
        self::assertEquals($timestamp, $log->getTimestamp());

        $log->setType(Log::TYPE_ERROR);
        self::assertEquals(Log::TYPE_ERROR, $log->getType());
        $log->setType(Log::TYPE_DEBUG);
        self::assertEquals(Log::TYPE_DEBUG, $log->getType());
        $log->setType(Log::TYPE_WARNING);
        self::assertEquals(Log::TYPE_WARNING, $log->getType());
        $log->setType(Log::TYPE_VERBOSE);
        self::assertEquals(Log::TYPE_VERBOSE, $log->getType());
        $log->setType(Log::TYPE_INFO);
        self::assertEquals(Log::TYPE_INFO, $log->getType());

        $log->setMessage("Cannot read 'user' of undefined");
        self::assertEquals("Cannot read 'user' of undefined", $log->getMessage());

        $log->setVersion("0.11.0");
        self::assertEquals("0.11.0", $log->getVersion());

        $log->setEnvironment(Log::ENVIRONMENT_PRODUCTION);
        self::assertEquals(Log::ENVIRONMENT_PRODUCTION, $log->getEnvironment());
        $log->setEnvironment(Log::ENVIRONMENT_STAGING);
        self::assertEquals(Log::ENVIRONMENT_STAGING, $log->getEnvironment());

        $log->setNamespace("getAuthUser");
        self::assertEquals("getAuthUser", $log->getNamespace());

        $log->setAction("authGuard");
        self::assertEquals("authGuard", $log->getAction());

        $log->setServer("aws-001");
        self::assertEquals("aws-001", $log->getServer());

        $log->addExtra('isLoggedIn', false);
        self::assertEquals([ 'isLoggedIn' => false ], $log->getExtra());

        $log->addTag('authMethod', 'session');
        $log->addTag('authProvider', 'basic');
        self::assertEquals([ 'authMethod' => 'session', 'authProvider' => 'basic' ], $log->getTags());

        $user = new User("myid123");
        $log->setUser($user);
        self::assertEquals($user, $log->getUser());
        self::assertEquals("myid123", $log->getUser()->getId());

        $breadcrumb = new Breadcrumb(Log::TYPE_DEBUG, "http", "DELETE /api/v1/database/abcd1234/efgh5678", $timestamp);
        $log->addBreadcrumb($breadcrumb);
        self::assertEquals([$breadcrumb], $log->getBreadcrumbs());
        self::assertEquals(Log::TYPE_DEBUG, $log->getBreadcrumbs()[0]->getType());
        self::assertEquals("http", $log->getBreadcrumbs()[0]->getCategory());
        self::assertEquals("DELETE /api/v1/database/abcd1234/efgh5678", $log->getBreadcrumbs()[0]->getMessage());
        self::assertEquals($timestamp, $log->getBreadcrumbs()[0]->getTimestamp());

    }

    /**
     * @throws Exception
     */
    public function testLogBreadcrumb()
    {
        $timestamp = \microtime(true);
        $breadcrumb = new Breadcrumb(Log::TYPE_DEBUG, "http", "POST /user", $timestamp);

        self::assertEquals(Log::TYPE_DEBUG, $breadcrumb->getType());
        self::assertEquals("http", $breadcrumb->getCategory());
        self::assertEquals("POST /user", $breadcrumb->getMessage());
        self::assertEquals($timestamp, $breadcrumb->getTimestamp());

        $breadcrumb = new Breadcrumb(Log::TYPE_INFO, "http", "POST /user", $timestamp);
        self::assertEquals(Log::TYPE_INFO, $breadcrumb->getType());
        $breadcrumb = new Breadcrumb(Log::TYPE_VERBOSE, "http", "POST /user", $timestamp);
        self::assertEquals(Log::TYPE_VERBOSE, $breadcrumb->getType());
        $breadcrumb = new Breadcrumb(Log::TYPE_ERROR, "http", "POST /user", $timestamp);
        self::assertEquals(Log::TYPE_ERROR, $breadcrumb->getType());
        $breadcrumb = new Breadcrumb(Log::TYPE_WARNING, "http", "POST /user", $timestamp);
        self::assertEquals(Log::TYPE_WARNING, $breadcrumb->getType());

        // Assert FAILS
        self::expectException(ArgumentCountError::class);
        $breadcrumb = new Breadcrumb();
        $breadcrumb = new Breadcrumb(Log::TYPE_DEBUG);
        $breadcrumb = new Breadcrumb(Log::TYPE_DEBUG, "http");
        $breadcrumb = new Breadcrumb(Log::TYPE_DEBUG, "http", "POST /user");
    }

    /**
     * @throws Exception
     */
    public function testAdapters()
    {
        // Prepare log
        $log = new Log();
        $log->setAction("controller.database.deleteDocument");
        $log->setEnvironment("production");
        $log->setNamespace("api");
        $log->setServer("digitalocean-us-001");
        $log->setType(Log::TYPE_WARNING);
        $log->setVersion("0.11.5");
        $log->setMessage("Document efgh5678 not found");
        $log->setUser(new User("efgh5678"));
        $log->addBreadcrumb(new Breadcrumb(Log::TYPE_DEBUG, "http", "DELETE /api/v1/database/abcd1234/efgh5678", \microtime(true) - 500));
        $log->addBreadcrumb(new Breadcrumb(Log::TYPE_DEBUG, "auth", "Using API key", \microtime(true) - 400));
        $log->addBreadcrumb(new Breadcrumb(Log::TYPE_INFO, "auth", "Authenticated with * Using API Key", \microtime(true) - 350));
        $log->addBreadcrumb(new Breadcrumb(Log::TYPE_INFO, "database", "Found collection abcd1234", \microtime(true) - 300));
        $log->addBreadcrumb(new Breadcrumb(Log::TYPE_DEBUG, "database", "Permission for collection abcd1234 met", \microtime(true) - 200));
        $log->addBreadcrumb(new Breadcrumb(Log::TYPE_ERROR, "database", "Missing document when searching by ID!", \microtime(true)));
        $log->addTag('sdk', 'Flutter');
        $log->addTag('sdkVersion', '0.0.1');
        $log->addTag('authMode', 'default');
        $log->addTag('authMethod', 'cookie');
        $log->addTag('authProvider', 'MagicLink');
        $log->addExtra('urgent', false);
        $log->addExtra('isExpected', true);

        // Test Sentry
        $adapter = new Sentry(\getenv("TEST_SENTRY_KEY") . ';' . \getenv("TEST_SENTRY_PROJECT_ID"));
        $logger = new Logger($adapter);
        $response = $logger->addLog($log);
        // self::assertEquals(200, $response);

        // Test AppSignal
        $adapter = new AppSignal(\getenv("TEST_APPSIGNAL_KEY"));
        $logger = new Logger($adapter);
        $response = $logger->addLog($log);
        // self::assertEquals(200, $response);

        // Test Raygun
        $adapter = new Raygun(\getenv("TEST_RAYGUN_KEY"));
        $logger = new Logger($adapter);
        $response = $logger->addLog($log);
        // self::assertEquals(202, $response);
    }
}