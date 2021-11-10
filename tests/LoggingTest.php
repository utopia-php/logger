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

use Utopia\Logging\Adapter\Sentry;
use Utopia\Logging\Issue;
use Utopia\Logging\Breadcrumb;
use Utopia\Logging\User;
use Utopia\Logging\Logging;

class LoggingTest extends TestCase
{
    public function testSentry()
    {
        $adapter = new Sentry(\getenv("TEST_SENTRY_KEY"), \getenv("TEST_SENTRY_PROJECT_ID"));
        $logging = new Logging($adapter);

        $issue = new Issue();
        $issue->setAction("controller.database.deleteDocument");
        $issue->setEnvironment("production");
        $issue->setLogger("api");
        $issue->setServer("digitalocean-us-001");
        $issue->setType("warning");
        $issue->setVersion("0.11.5");
        $issue->setMessage("Document efgh5678 not found");
        $issue->setUser(new User("efgh5678"));
        $issue->setBreadcrumbs([
            new Breadcrumb("debug", "http", "DELETE /api/v1/database/abcd1234/efgh5678", \microtime(true) - 500),
            new Breadcrumb("debug", "auth", "Using API key", \microtime(true) - 400),
            new Breadcrumb("info", "auth", "Authenticated with * Using API Key", \microtime(true) - 350),
            new Breadcrumb("info", "database", "Found collection abcd1234", \microtime(true) - 300),
            new Breadcrumb("debug", "database", "Permission for collection abcd1234 met", \microtime(true) - 200),
            new Breadcrumb("error", "database", "Missing document when searching by ID!", \microtime(true)),
        ]);
        $issue->setTags([
            'sdk' => 'Flutter',
            'sdkVersion' => '0.0.1',
            'authMode' => 'default',
            'authMethod' => 'cookie',
            'authProvider' => 'MagicLink'
        ]);
        $issue->setExtra([
            'urgent' => false,
            'isExpected' => true
        ]);

        $response = $logging->addIssue($issue);

        self::assertEquals(200, $response);
    }
}