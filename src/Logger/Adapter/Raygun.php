<?php

namespace Utopia\Logger\Adapter;

use Utopia\Logger\Adapter;
use Utopia\Logger\Log;
use Utopia\Logger\Logger;

// Reference Material
// https://raygun.com/documentation/product-guides/crash-reporting/api/

class Raygun extends Adapter
{
    /**
     * @var string (required, can be found in Appsignal -> Project -> App Settings -> Push & deploy -> Push Key)
     */
    protected string $apiKey;

    /**
     * Raygun constructor.
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
        return 'raygun';
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
                'category' => $breadcrumb->getCategory(),
                'message' => $breadcrumb->getMessage(),
                'type' => $breadcrumb->getType(),
                'level' => 'request',
                'timestamp' => \intval($breadcrumb->getTimestamp()),
            ]);
        }

        $tagsArray = [];

        foreach ($log->getTags() as $tagKey => $tagValue) {
            \array_push($tagsArray, $tagKey.': '.$tagValue);
        }

        \array_push($tagsArray, 'type: '.$log->getType());
        \array_push($tagsArray, 'environment: '.$log->getEnvironment());
        \array_push($tagsArray, 'sdk: utopia-logger/'.Logger::LIBRARY_VERSION);

        // prepare log (request body)
        $requestBody = [
            'occurredOn' => \intval($log->getTimestamp()),
            'details' => [
                'machineName' => $log->getServer(),
                'groupingKey' => $log->getNamespace(),
                'version' => $log->getVersion(),
                'error' => [
                    'className' => $log->getAction(),
                    'message' => $log->getMessage(),
                ],
                'tags' => $tagsArray,
                'userCustomData' => $log->getExtra(),
                'user' => [
                    'isAnonymous' => empty($log->getUser()),
                    'identifier' => empty($log->getUser()) ? null : $log->getUser()->getId(),
                    'email' => empty($log->getUser()) ? null : $log->getUser()->getEmail(),
                    'fullName' => empty($log->getUser()) ? null : $log->getUser()->getUsername(),
                ],
                'breadcrumbs' => $breadcrumbsArray,
            ],
        ];

        // init curl object
        $ch = \curl_init();

        // define options
        $optArray = [
            CURLOPT_URL => 'https://api.raygun.com/entries',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => \json_encode($requestBody),
            CURLOPT_HEADEROPT => \CURLHEADER_UNIFIED,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-ApiKey: '.$this->apiKey],
        ];

        // apply those options
        \curl_setopt_array($ch, $optArray);

        // execute request and get response
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, \CURLINFO_HTTP_CODE);
        $curlError = \curl_errno($ch);
        \curl_close($ch);

        if ($curlError !== CURLE_OK || $httpCode === 0) {
            error_log("Raygun push failed with curl error ({$curlError}): {$response}");

            return 500;
        }

        if ($httpCode >= 400) {
            error_log("Raygun push failed with status code {$httpCode}: {$curlError} ({$response})");
        }

        return $httpCode;
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
