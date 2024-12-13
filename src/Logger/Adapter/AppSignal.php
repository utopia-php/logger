<?php

namespace Utopia\Logger\Adapter;

use Exception;
use Utopia\Logger\Adapter;
use Utopia\Logger\Log;
use Utopia\Logger\Logger;

// Reference Material
// https://docs.appsignal.com/api/public-endpoint/errors.html

class AppSignal extends Adapter
{
    /**
     * @var string (required, can be found in Appsignal -> Project -> App Settings -> Push & deploy -> Push Key)
     */
    protected string $apiKey;

    /**
     * AppSignal constructor.
     *
     * @param  string  $key
     */
    public function __construct(string $key)
    {
        $this->apiKey = $key;
    }

    /**
     * Return unique adapter name
     *
     * @return string
     */
    public static function getName(): string
    {
        return 'appSignal';
    }

    /**
     * Push log to external provider
     *
     * @param  Log  $log
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
                'timestamp' => \intval($breadcrumb->getTimestamp()),
                'category' => $breadcrumb->getCategory(),
                'action' => $breadcrumb->getMessage(),
                'metadata' => [
                    'type' => $breadcrumb->getType(),
                ],
            ]);
        }

        $tags = [];

        foreach ($log->getTags() as $tagKey => $tagValue) {
            $tags[$tagKey] = $tagValue;
        }

        if (! empty($log->getType())) {
            $tags['type'] = $log->getType();
        }
        if (! empty($log->getUser()) && ! empty($log->getUser()->getId())) {
            $tags['userId'] = $log->getUser()->getId();
        }
        if (! empty($log->getUser()) && ! empty($log->getUser()->getUsername())) {
            $tags['userName'] = $log->getUser()->getUsername();
        }
        if (! empty($log->getUser()) && ! empty($log->getUser()->getEmail())) {
            $tags['userEmail'] = $log->getUser()->getEmail();
        }

        $tags['sdk'] = 'utopia-logger/'.Logger::LIBRARY_VERSION;

        $requestBody = [
            'timestamp' => \intval($log->getTimestamp()),
            'namespace' => $log->getNamespace(),
            'error' => [
                'name' => $log->getMessage(),
                'message' => $log->getMessage(),
                'backtrace' => [],
            ],
            'environment' => [
                'environment' => $log->getEnvironment(),
                'server' => $log->getServer(),
                'version' => $log->getVersion(),
            ],
            'revision' => $log->getVersion(),
            'action' => $log->getAction(),
            'params' => $params,
            'tags' => $tags,
            'breadcrumbs' => $breadcrumbsArray,
        ];

        // init curl object
        $ch = \curl_init();

        // define options
        $optArray = [
            CURLOPT_URL => 'https://appsignal-endpoint.net/collect?api_key='.$this->apiKey.'&version=1.3.19',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => \json_encode($requestBody),
            CURLOPT_HEADEROPT => \CURLHEADER_UNIFIED,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ];

        // apply those options
        \curl_setopt_array($ch, $optArray);

        // execute request and get response
        $result = \curl_exec($ch);
        $response = \curl_getinfo($ch, \CURLINFO_HTTP_CODE);
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
            Log::TYPE_VERBOSE,
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
            Log::TYPE_VERBOSE,
            Log::TYPE_WARNING,
            Log::TYPE_ERROR,
        ];
    }
}
