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
     * Return unique adapter name
     *
     * @return string
     */
    public static function getName(): string
    {
        return "sentry";
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
        $breadcrumbsObject = $log->getBreadcrumbs();
        $breadcrumbsArray = [];

        foreach ($breadcrumbsObject as $breadcrumb) {
            \array_push($breadcrumbsArray, [
                'type' => "default",
                'level' => $breadcrumb->getType(),
                'category' => $breadcrumb->getCategory(),
                'message' => $breadcrumb->getMessage(),
                'timestamp' => $breadcrumb->getTimestamp()
            ]);
        }

        // prepare log (request body)
        $requestBody = [
            'timestamp' => $log->getTimestamp(),
            'platform' => 'php',
            'level' => 'error',
            'logger' => $log->getNamespace(),
            'transaction' =>  $log->getAction(),
            'server_name' =>  $log->getServer(),
            'release' => $log->getVersion(),
            'environment' => $log->getEnvironment(),
            'message' => [
                'message' => $log->getMessage()
            ],
            'tags'=> $log->getTags(),
            'extra'=> $log->getExtra(),
            'breadcrumbs'=> $breadcrumbsArray,
            'user'=> empty($log->getUser()) ? null : [
                'id' => $log->getUser()->getId(),
                'email' => $log->getUser()->getEmail(),
                'username' => $log->getUser()->getUsername(),
            ]
        ];

        // init curl object
        $ch = \curl_init();

        // define options
        $optArray = array(
            CURLOPT_URL => 'https://sentry.io/api/' . $this->projectId . '/store/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => \json_encode($requestBody),
            CURLOPT_HEADEROPT => \CURLHEADER_UNIFIED,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'X-Sentry-Auth: Sentry sentry_version=7, sentry_key=' . $this->sentryKey . ', sentry_client=utopia-logger/' . Logger::LIBRARY_VERSION)
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
     * Sentry constructor.
     *
     * @param string $configKey
     */
    public function __construct(string $configKey)
    {
        $configChunks = \explode(";", $configKey);
        $this->sentryKey = $configChunks[0];
        $this->projectId = $configChunks[1];
    }


    public function getSupportedTypes(): array
    {
        return [
            Log::TYPE_INFO,
            Log::TYPE_DEBUG,
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
            Log::TYPE_WARNING,
            Log::TYPE_ERROR
        ];
    }
}