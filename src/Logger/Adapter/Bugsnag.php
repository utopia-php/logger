<?php

namespace Utopia\Logger\Adapter;

use Exception;
use Utopia\Logger\Adapter;
use Utopia\Logger\Log;
use Utopia\Logger\Logger;

// Reference Material
// https://bugsnagerrorreportingapi.docs.apiary.io/#reference/0/notify/send-error-reports

class Bugsnag extends Adapter
{
    /**
     * @var string (required, can be found in Bugsnag -> Project -> Project settings -> Notifier API key)
     */
    protected string $apiKey;

    /**
     * Return unique adapter name
     *
     * @return string
     */
    public static function getName(): string
    {
        return "bugsnag";
    }

    /**
     * Push log to external provider
     *
     * @param Log $log
     * @return int
     * @throws Exception
     */
    public function push(Log $log): int
    {
        $line = isset($log->getExtra()['line']) ? $log->getExtra()['line'] : '';
        $file = isset($log->getExtra()['file']) ? $log->getExtra()['file'] : '';
        $id = empty($log->getUser()) ? null : $log->getUser()->getId();
        $email = empty($log->getUser()) ? null : $log->getUser()->getEmail();
        $username = empty($log->getUser()) ? null : $log->getUser()->getUsername();
        
        $breadcrumbsObject = $log->getBreadcrumbs();
        $breadcrumbsArray = [];

        foreach ($breadcrumbsObject as $breadcrumb) {
            \array_push($breadcrumbsArray, [
                'timestamp' => date('c', \intval($breadcrumb->getTimestamp())),
                'name' => $breadcrumb->getMessage(),
                'type' => 'manual',
                'metadata' => [
                    'type' => $breadcrumb->getType(),
                    'category' => $breadcrumb->getCategory()
                ]
            ]);
        }

        $stackFrames = [];

        if (isset($log->getExtra()['detailedTrace'])) {
            foreach ($log->getExtra()['detailedTrace'] as $trace) {
                \array_push($stackFrames, [
                    'file' => $trace['file'],
                    'lineNumber' => $trace['line'],
                    'method' => $trace['function'],
                ]);
            }
        }

        // prepare log (request body)
        $requestBody = [
            'payloadVersion' => '5',
            'notifier' => [
                'name'=> 'utopia-logger',
                'version' => $log->getVersion(),
                'url' => 'https://github.com/utopia-php/logger',
            ],
            'events' => array(
                'exceptions' => array(
                    'errorClass' => $log->getType(),
                    'message' => $log->getMessage(),
                    'stacktrace' => $stackFrames,
                    'type' => 'php'
                ),
                'breadcrumbs' => $breadcrumbsArray,
                'context' => $log->getAction(),
                'groupingHash' => $log->getNamespace(),
                'user' => [
                    'id' => $id,
                    'email' => $email,
                    'name' => $username
                ],
                'app' => [
                    'releaseStage' => $log->getEnvironment()
                ],
                'device' => [
                    'hostname' => $log->getServer(),
                    'time' => date('c', \intval($log->getTimestamp()))
                ],
                'metaData' => $log->getTags()
            )
        ];

        // init curl object
        $ch = \curl_init();

        // define options
        $optArray = array(
            CURLOPT_URL => 'https://notify.bugsnag.com/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => \json_encode($requestBody),
            CURLOPT_HEADEROPT => \CURLHEADER_UNIFIED,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'Bugsnag-Api-Key: ' . $this->apiKey, 'Bugsnag-Payload-Version: 5')
        );

        // apply those options
        \curl_setopt_array($ch, $optArray);

        // execute request and get response
        $result = curl_exec($ch);
        $response = curl_getinfo($ch, \CURLINFO_HTTP_CODE);

        if(!$result && $response >= 400) {
            throw new Exception("Log could not be pushed with status code " . $response . ": " . \curl_error($ch));
        }

        \curl_close($ch);

        return $response;
    }

    /**
     * Bugsnag constructor.
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
            Log::ENVIRONMENT_PRODUCTION
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