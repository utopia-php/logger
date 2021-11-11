<?php

namespace Utopia\Logging\Adapter;

use Utopia\Logging\Adapter;
use Utopia\Logging\Log;

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
    public function getAdapterName(): string
    {
        return "sentry";
    }

    /**
     * Push log to external provider
     *
     * @param Log $log
     * @return int
     */
    public function pushLog(Log $log): int
    {
        $breadcrumbsObject = $log->getBreadcrumbs();
        $breadcrumbsArray = [];

        foreach ($breadcrumbsObject as $breadcrumb) {
            \array_push($breadcrumbsArray, [
                'type' => $breadcrumb->getType(),
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
            'logger' => $log->getLogger(),
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
            'user'=> [
                'id' => $log->getUser()->getId(),
                'email' => $log->getUser()->getEmail(),
                'username' => $log->getUser()->getUsername(),
            ]
        ];

        // init curl object
        $ch = curl_init();

        // define options
        $optArray = array(
            CURLOPT_URL => 'https://sentry.io/api/' . $this->projectId . '/store/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => \json_encode($requestBody),
            CURLOPT_HEADEROPT => CURLHEADER_UNIFIED,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'X-Sentry-Auth: Sentry sentry_version=7, sentry_key=' . $this->sentryKey . ', sentry_client=utopia-logging/1.0')
            // TODO: ^ Automatically figure out version (1.0)
        );

        // apply those options
        curl_setopt_array($ch, $optArray);

        // execute request and get response
        $result = curl_exec($ch);

        // also get the error and response code
        $errors = curl_error($ch);
        $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return $response;
    }

    /**
     * Sentry constructor.
     *
     * @param string $sentryKey
     * @param string $projectId
     */
    public function __construct(string $sentryKey, string $projectId)
    {
        $this->sentryKey = $sentryKey;
        $this->projectId = $projectId;
    }
}