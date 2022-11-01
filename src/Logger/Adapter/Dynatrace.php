<?php

namespace Utopia\Logger\Adapter;

use Exception;
use Utopia\Logger\Adapter;
use Utopia\Logger\Log;
use Utopia\Logger\Logger;

// Reference Material
// https://www.dynatrace.com/support/help/dynatrace-api/environment-api/events-v2

class Dynatrace extends Adapter
{
    /**
     * @var string (required, can be found in Dynatrace -> Access tokens)
     */
    protected string $apiKey;

    /**
     * @var string (required, this is part of Dynatrace endpoint: 'https://{{THIS_PART}}.live.dynatrace.com/api/v2/events/ingest')
     */
    protected string $environmentId;

    /**
     * Return unique adapter name
     *
     * @return string
     */
    public static function getName(): string
    {
        return "dynatrace";
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
        $id = empty($log->getUser()) ? null : $log->getUser()->getId();
        $email = empty($log->getUser()) ? null : $log->getUser()->getEmail();
        $username = empty($log->getUser()) ? null : $log->getUser()->getUsername();
        
        $breadcrumbsObject = $log->getBreadcrumbs();
        $breadcrumbsArray = [];

        foreach ($breadcrumbsObject as $breadcrumb) {
            \array_push($breadcrumbsArray, [
                'type' => $breadcrumb->getType(),
                'category' => $breadcrumb->getCategory(),
                'message' => $breadcrumb->getMessage(),
                'timestamp' => \intval($breadcrumb->getTimestamp())
            ]);
        }

        $stackFrames = [];

        if (isset($log->getExtra()['detailedTrace'])) {
            foreach ($log->getExtra()['detailedTrace'] as $trace) {
                \array_push($stackFrames, [
                    'filename' => $trace['file'],
                    'lineno' => $trace['line'],
                    'function' => $trace['function'],
                ]);
            }
        }

        $tags = array();

        foreach ($log->getTags() as $tagKey => $tagValue) {
            $tags[$tagKey] = $tagValue;
        }

        if (!empty($log->getType())) {
            $tags['type'] = $log->getType();
        }
        if (!empty($log->getVersion())) {
            $tags['version'] = $log->getVersion();
        }
        if (!empty($log->getEnvironment())) {
            $tags['environment'] = $log->getEnvironment();
        }
        if (!empty($log->getAction())) {
            $tags['action'] = $log->getAction();
        }
        if (!empty($log->getNamespace())) {
            $tags['namespace'] = $log->getNamespace();
        }
        if (!empty($log->getServer())) {
            $tags['server'] = $log->getServer();
        }

        $tags['userId'] = $id;
        $tags['userEmail'] = $email;
        $tags['userName'] = $username;
        $tags['stacktrace'] = $stackFrames;
        $tags['breadcrumbs'] = $breadcrumbsArray;

        // prepare log (request body)
        $requestBody = [
            'eventType' => 'ERROR_EVENT',
            'title' => $log->getMessage(),
            'startTime' => \intval($log->getTimestamp()),
            'endTime' => \intval($log->getTimestamp()),
            'properties' => $tags
        ];

        // init curl object
        $ch = \curl_init();

        // define options
        $optArray = array(
            CURLOPT_URL => 'https://' . $this->environmentId . '.live.dynatrace.com/api/v2/events/ingest',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => \json_encode($requestBody),
            CURLOPT_HEADEROPT => \CURLHEADER_UNIFIED,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'Authorization: Api-Token ' . $this->apiKey)
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
     * Dynatrace constructor.
     *
     * @param string $configKey
     */
    public function __construct(string $configKey)
    {
        $configChunks = \explode(";", $configKey);
        $this->apiKey = $configChunks[0];
        $this->environmentId = $configChunks[1];
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