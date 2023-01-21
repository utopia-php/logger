<?php

namespace Utopia\Logger\Adapter;

use Exception;
use Utopia\Logger\Adapter;
use Utopia\Logger\Log;
use Utopia\Logger\Logger;

// Reference Material
// https://docs.honeybadger.io/api/reporting-exceptions

class HoneyBadger extends Adapter
{
    protected string $apiKey;

    /**
     * Return unique adapter name
     *
     * @return string
     */
    public static function getName(): string
    {
        return "honeyBadger";
    }

    /**
     * Push log to external provider
     *
     * @param Log $log
     * @return int
     */
    public function push(Log $log): int
    {
        $params = [];

        foreach ($log->getExtra() as $paramKey => $paramValue) {
            $params[$paramKey] = var_export($paramValue, true);
        }

        $breadcrumbsObject = $log->getBreadcrumbs();
        $breadcrumbsArray = [];

        foreach ($breadcrumbsObject as $breadcrumb) {
            \array_push($breadcrumbsArray, [
                'category' => $breadcrumb->getCategory(),
                'timestamp' => \intval($breadcrumb->getTimestamp()),
                'message' => $breadcrumb->getMessage(),
                'metadata' => [
                    'exception' => $breadcrumb->getType()
                ]
            ]);
        }

        $stackFrames = [];

        if (isset($log->getExtra()['stackTrace'])) {
            $detailedTrace = $log->getExtra()['stackTrace'];
            if (!is_array($detailedTrace)) {
                throw new Exception("stackTrace must be an array");
            }
            foreach ($detailedTrace as $trace) {
                if (!is_array($trace)) {
                    throw new Exception("stackTrace must be an array of arrays");
                }
                \array_push($stackFrames, [
                    'number' => $trace['number'],
                    'file' => $trace['file'],
                    'method' => $trace['method'],
                ]);
            }
        }

        $requestBody = [
            'notifier' => [
                'name' => 'utopia-logger',
                'url' => 'https://github.com/utopia-php/logger',
                'tags' => $log->getTags(),
                'version' => $log->getVersion(),
            ],
            'error' => [
                'class' => $log->getType(),
                'message' => $log->getMessage(),
                'backtrace' => $stackFrames,
            ],
            'breadcrumbs' => [
                "enabled" => true,
                "trail" => $breadcrumbsArray
            ],

            'request' => [
                'params' => $params,
                'action' => $log->getAction(),
                'context' => empty($log->getUser()) ? null : [
                    'user_id' => $log->getUser()->getId(),
                    'user_email' => $log->getUser()->getEmail(),
                ]

            ],
            'server' => [
                'project_root' => $log->getServer(),
                'environment_name' => $log->getEnvironment(),
                'hostname' => $log->getServer(),
            ],

        ];


        // init curl object
        $ch = \curl_init();

        // define options
        $optArray = array(
            CURLOPT_URL => 'https://api.honeybadger.io/v1/notices',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => \json_encode($requestBody),
            CURLOPT_HEADEROPT => \CURLHEADER_UNIFIED,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'X-API-Key: ' . $this->apiKey, 'Accept: application/json', 'User-Agent: utopia-logger/' . Logger::LIBRARY_VERSION)
        );

        // apply those options
        \curl_setopt_array($ch, $optArray);

        // execute request and get response
        $result = \curl_exec($ch);
        $response = \curl_getinfo($ch, \CURLINFO_HTTP_CODE);


        if (!$result && $response >= 400) {
            throw new Exception("Log could not be pushed with status code " . $response . ": " . \curl_error($ch));
        }

        \curl_close($ch);

        return $response;
    }
    /**
     * HoneyBadger constructor.
     *
     * @param string $configKey
     */
    public function __construct(string $configKey)
    {
        $this->apiKey = $configKey;
    }

    public function getSupportedTypes(): array
    {
        return [
            Log::TYPE_INFO,
            Log::TYPE_DEBUG,
            Log::TYPE_VERBOSE,
            Log::TYPE_WARNING,
            Log::TYPE_ERROR
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
            Log::TYPE_VERBOSE,
            Log::TYPE_WARNING,
            Log::TYPE_ERROR
        ];
    }
}
