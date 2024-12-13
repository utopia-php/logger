<?php

namespace Utopia\Logger\Adapter;

use Exception;
use Utopia\Logger\Adapter;
use Utopia\Logger\Log;
use Utopia\Logger\Logger;

// Reference Material
// https://develop.sentry.dev/sdk/event-payloads/

class Sentry extends Adapter
{
    /**
     * @var string (required, this part of Sentry DSN: 'https://{{THIS_PART}}@blabla.ingest.sentry.io/blabla')
     */
    protected string $sentryKey;

    /**
     * @var string (required, this part of Sentry DSN: 'https://blabla@blabla.ingest.sentry.io/{{THIS_PART}}')
     */
    protected string $projectId;

    /**
     * @var string (optional, the host where Sentry is reachable, in case of self-hosted Sentry could
     *              look like 'https://sentry.mycompany.com'. defaults to 'https://sentry.io')
     */
    protected string $sentryHost;

    /**
     * Sentry constructor.
     *
     * @param  string  $projectId
     * @param  string  $key
     * @param  string  $host
     */
    public function __construct(string $projectId, string $key, string $host = '')
    {
        if (empty($host)) {
            $host = 'https://sentry.io';
        }

        $this->sentryHost = $host;
        $this->sentryKey = $key;
        $this->projectId = $projectId;
    }

    /**
     * Return unique adapter name
     *
     * @return string
     */
    public static function getName(): string
    {
        return 'sentry';
    }

    /**
     * Push log to external provider
     *
     * @param  Log  $log
     * @return int
     */
    public function push(Log $log): int
    {
        $breadcrumbsObject = $log->getBreadcrumbs();
        $breadcrumbsArray = [];

        foreach ($breadcrumbsObject as $breadcrumb) {
            \array_push($breadcrumbsArray, [
                'type' => 'default',
                'level' => $breadcrumb->getType(),
                'category' => $breadcrumb->getCategory(),
                'message' => $breadcrumb->getMessage(),
                'timestamp' => $breadcrumb->getTimestamp(),
            ]);
        }

        $stackFrames = [];

        if (isset($log->getExtra()['detailedTrace'])) {
            $detailedTrace = $log->getExtra()['detailedTrace'];
            if (! is_array($detailedTrace)) {
                throw new Exception('detailedTrace must be an array');
            }
            foreach ($detailedTrace as $trace) {
                if (! is_array($trace)) {
                    throw new Exception('detailedTrace must be an array of arrays');
                }
                \array_push($stackFrames, [
                    'filename' => $trace['file'] ?? '',
                    'lineno' => $trace['line'] ?? 0,
                    'function' => $trace['function'] ?? '',
                ]);
            }
        }

        // Reverse array (because Sentry expects the list to go from the oldest to the newest calls)
        $stackFrames = \array_reverse($stackFrames);

        // prepare log (request body)
        $requestBody = [
            'timestamp' => $log->getTimestamp(),
            'platform' => 'php',
            'level' => 'error',
            'logger' => $log->getNamespace(),
            'transaction' => $log->getAction(),
            'server_name' => $log->getServer(),
            'release' => $log->getVersion(),
            'environment' => $log->getEnvironment(),
            'message' => [
                'message' => $log->getMessage(),
            ],
            'exception' => [
                'values' => [
                    [
                        'type' => $log->getMessage(),
                        'stacktrace' => [
                            'frames' => $stackFrames,
                        ],
                    ],
                ],
            ],
            'tags' => $log->getTags(),
            'extra' => $log->getExtra(),
            'breadcrumbs' => $breadcrumbsArray,
            'user' => empty($log->getUser()) ? null : [
                'id' => $log->getUser()->getId(),
                'email' => $log->getUser()->getEmail(),
                'username' => $log->getUser()->getUsername(),
            ],
        ];

        // init curl object
        $ch = \curl_init();

        // define options
        $optArray = [
            CURLOPT_URL => $this->sentryHost.'/api/'.$this->projectId.'/store/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => \json_encode($requestBody),
            CURLOPT_HEADEROPT => \CURLHEADER_UNIFIED,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Sentry-Auth: Sentry sentry_version=7, sentry_key='.$this->sentryKey.', sentry_client=utopia-logger/'.Logger::LIBRARY_VERSION],
        ];

        // apply those options
        \curl_setopt_array($ch, $optArray);

        // execute request and get response
        $result = curl_exec($ch);
        $response = curl_getinfo($ch, \CURLINFO_HTTP_CODE);
        $error = \curl_error($ch);

        if ($response >= 400 || $response === 0) {
            error_log("Log could not be pushed with status code {$response}: {$result} ({$error})");
        }

        \curl_close($ch);

        return $response;
    }

    public function getSupportedTypes(): array
    {
        return [
            Log::TYPE_INFO,
            Log::TYPE_DEBUG,
            Log::TYPE_WARNING,
            Log::TYPE_ERROR,
        ];
    }

    public function getSupportedEnvironments(): array
    {
        return [
            Log::ENVIRONMENT_STAGING,
            Log::ENVIRONMENT_PRODUCTION,
        ];
    }

    public function getSupportedBreadcrumbTypes(): array
    {
        return [
            Log::TYPE_INFO,
            Log::TYPE_DEBUG,
            Log::TYPE_WARNING,
            Log::TYPE_ERROR,
        ];
    }
}
