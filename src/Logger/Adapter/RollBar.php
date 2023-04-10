<?php

namespace Utopia\Logger\Adapter;

use Exception;
use Utopia\Logger\Adapter;
use Utopia\Logger\Log;
use Utopia\Logger\Logger;

// Reference Material
// https://docs.rollbar.com/reference/create-item

class RollBar extends Adapter
{

    /**
     * @var string (required, can be found in Appsignal -> Project -> App Settings -> Push & deploy -> Push Key)
     */
    protected string $apiKey;

    /**
     * Return unique adapter name
     *
     * @return string
     */
    public static function getName(): string
    {
        return "rollBar";
    }

    /**
     * Push log to external provider
     *
     * @param Log $log
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
            ]);
        }

        $tagsArray = [];

        foreach ($log->getTags() as $tagKey => $tagValue) {
            \array_push($tagsArray, $tagKey . ': ' . $tagValue);
        }

        \array_push($tagsArray, 'type: ' . $log->getType());
        \array_push($tagsArray, 'environment: ' . $log->getEnvironment());
        \array_push($tagsArray, 'sdk: utopia-logger/' . Logger::LIBRARY_VERSION);


        if (! empty($log->getUser()) && ! empty($log->getUser()->getId())) {
            $tags['userId'] = $log->getUser()->getId();
        }
        if (! empty($log->getUser()) && ! empty($log->getUser()->getUsername())) {
            $tags['userName'] = $log->getUser()->getUsername();
        }
        if (! empty($log->getUser()) && ! empty($log->getUser()->getEmail())) {
            $tags['userEmail'] = $log->getUser()->getEmail();
        }

        // prepare log (request body)
        $requestBody = [
            'data' => [
                'environment' => $log->getEnvironment(),
                'body' => [
                    'message' => [
                        'body' => $log->getMessage()
                    ],
                    'timestamp' => \intval($log->getTimestamp()),
                ],
                'trace_chain' => $breadcrumbsArray,
                'level' => $log->getType(),
                'custom' => $tagsArray            
            ]
        ];



        // init curl object
        $ch = \curl_init();

        // define options
        $optArray = [
            CURLOPT_URL => 'https://api.rollbar.com/api/1/item',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => \json_encode($requestBody),
            CURLOPT_HEADEROPT => \CURLHEADER_UNIFIED,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Rollbar-Access-Token: ' . $this->apiKey],
        ];

        // apply those options
        \curl_setopt_array($ch, $optArray);

        // execute request and get response
        $result = \curl_exec($ch);
        $response = \curl_getinfo($ch, \CURLINFO_HTTP_CODE);

        if (!$result && $response >= 400) {
            throw new Exception('Log could not be pushed with status code ' . $response . ': ' . \curl_error($ch));
        }

        \curl_close($ch);

        return $response;

    }

    /**
     * RollBar constructor.
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