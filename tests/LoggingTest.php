<?php
/**
 * Utopia PHP Framework
 *
 * @package Logging
 * @subpackage Tests
 *
 * @link https://github.com/utopia-php/framework
 * @author Eldad Fux <eldad@appwrite.io>
 * @version 1.0 RC4
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

use PHPUnit\Framework\TestCase;

use Utopia\Logging\Adapter\AppSignal;
use Utopia\Logging\Adapter\Raygun;
use Utopia\Logging\Adapter\Sentry;
use Utopia\Logging\Log;
use Utopia\Logging\Log\Breadcrumb;
use Utopia\Logging\Log\User;
use Utopia\Logging\Logging;

class LoggingTest extends TestCase
{
    public function testLog()
    {
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

        // Test Sentry
        $adapter = new Sentry(\getenv("TEST_SENTRY_KEY"), \getenv("TEST_SENTRY_PROJECT_ID"));
        $logging = new Logging($adapter);
        $response = $logging->addLog($log);
        self::assertEquals(200, $response);

        // Test AppSignal
        $adapter = new AppSignal(\getenv("TEST_APPSIGNAL_KEY"));
        $logging = new Logging($adapter);
        $response = $logging->addLog($log);
        self::assertEquals(200, $response);

        // Test Raygun
        $adapter = new Raygun(\getenv("TEST_RAYGUN_KEY"));
        $logging = new Logging($adapter);
        $response = $logging->addLog($log);
        self::assertEquals(202, $response);
    }
}