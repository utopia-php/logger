<?php

namespace Utopia\Logger\Adapter;

use Utopia\Logger\Adapter;
use Utopia\Logger\Log;
use Utopia\Logger\Logger;

// Reference Material
// https://docs.logowl.io/docs/custom-adapter
// https://github.com/jz222/logowl-adapter-nodejs/blob/master/lib/broker/index.js

class LogOwl extends Adapter
{
    /**
     * @var string (required, can be found in LogOwl -> All Services -> Project -> Ticket -> Service Ticket)
     */
    protected string $ticket;

    /**
     * @var string (optional, the host where LogOwl is reachable, in case of self-hosted LogOwl could
     *              look like 'https://logowl.example.com'. defaults to 'https://api.logowl.io/logging/')
     */
    protected string $logOwlHost;

    /**
     * LogOwl constructor.
     *
     * @param  string  $ticket
     * @param  string  $host
     */
    public function __construct(string $ticket, string $host = '')
    {
        if (empty($host)) {
            $host = 'https://api.logowl.io/logging/';
        }

        $this->ticket = $ticket;
        $this->logOwlHost = $host;
    }

    /**
     * Return unique adapter name
     *
     * @return string
     */
    public static function getName(): string
    {
        return 'logOwl';
    }

    /**
     * Return adapter type
     *
     * @return string
     */
    public static function getAdapterType(): string
    {
        return 'utopia-logger';
    }

    /**
     * Return adapter version
     *
     * @return string
     */
    public static function getAdapterVersion(): string
    {
        return Logger::LIBRARY_VERSION;
    }

    /**
     * Push log to external provider
     *
     * @param  Log  $log
     * @return int
     */
    public function push(Log $log): int
    {
        $line = isset($log->getExtra()['line']) ? $log->getExtra()['line'] : '';
        $file = isset($log->getExtra()['file']) ? $log->getExtra()['file'] : '';
        $trace = isset($log->getExtra()['trace']) ? $log->getExtra()['trace'] : '';
        $id = empty($log->getUser()) ? null : $log->getUser()->getId();
        $email = empty($log->getUser()) ? null : $log->getUser()->getEmail();
        $username = empty($log->getUser()) ? null : $log->getUser()->getUsername();

        $breadcrumbsObject = $log->getBreadcrumbs();
        $breadcrumbsArray = [];

        foreach ($breadcrumbsObject as $breadcrumb) {
            \array_push($breadcrumbsArray, [
                'type' => 'log',
                'log' => $breadcrumb->getMessage(),
                'timestamp' => \intval($breadcrumb->getTimestamp()),
            ]);
        }

        // prepare log (request body)
        $requestBody = [
            'ticket' => $this->ticket,
            'message' => $log->getAction(),
            'path' => $file,
            'line' => $line,
            'stacktrace' => $trace,
            'badges' => [
                'environment' => $log->getEnvironment(),
                'namespace' => $log->getNamespace(),
                'version' => $log->getVersion(),
                'message' => $log->getMessage(),
                'id' => $id,
                '$email' => $email,
                '$username' => $username,
            ],
            'type' => $log->getType(),
            'metrics' => [
                'platform' => $log->getServer(),
            ],
            'logs' => $breadcrumbsArray,
            'timestamp' => \intval($log->getTimestamp()),
            'adapter' => [
                'name' => $this->getName(),
                'type' => $this->getAdapterType(),
                'version' => $this->getAdapterVersion(),
            ],
        ];

        // init curl object
        $ch = \curl_init();

        // define options
        $optArray = [
            CURLOPT_URL => $this->logOwlHost.$log->getType(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => \json_encode($requestBody),
            CURLOPT_HEADEROPT => \CURLHEADER_UNIFIED,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ];

        // apply those options
        \curl_setopt_array($ch, $optArray);

        // execute request and get response
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, \CURLINFO_HTTP_CODE);
        $curlError = \curl_error($ch);
        \curl_close($ch);

        if ($curlError !== CURLE_OK) {
            error_log("LogOwl push failed with curl error ({$curlError}): {$response}");

            return 500;
        }

        if ($httpCode >= 400 || $httpCode === 0) {
            error_log("LogOwl push failed with status code {$httpCode}: {$curlError} ({$response})");

            return $httpCode ?: 500;
        }

        return $httpCode;
    }

    public function getSupportedTypes(): array
    {
        return [
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
