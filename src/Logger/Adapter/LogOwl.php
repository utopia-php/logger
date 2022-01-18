<?php

namespace Utopia\Logger\Adapter;

use Exception;
use Utopia\Logger\Adapter;
use Utopia\Logger\Log;
use Utopia\Logger\Logger;

// Reference Material
// https://docs.logowl.io/docs/
// https://github.com/jz222/logowl-adapter-nodejs/tree/master/lib

class LogOwl extends Adapter
{
    /**
     * @var string (required, can be found in LogOwl -> All Services -> Project -> Ticket -> Service Ticket)
     */
    protected string $ticket;

    /**
     * Return unique adapter name
     *
     * @return string
     */
    public static function getName(): string
    {
        return "logOwl";
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
        // TODO: Implement HTTP API request that submit a log into external server. For building HTTP request, use `curl_exec()`, just like all other adapters
        
        // prepare log (request body)
        $requestBody = [
            'ticket' => $this->ticket,
            'message' => $log->getMessage(),
            'timestamp' => \intval($log->getTimestamp())
        ];

        // init curl object
        $ch = \curl_init();

        // define options
        $optArray = array(
            CURLOPT_URL => 'https://api.logowl.io/logging/' . $log->getType(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => \json_encode($requestBody),
            CURLOPT_HEADEROPT => \CURLHEADER_UNIFIED,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json')
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
     * [ADAPTER_NAME] constructor.
     *
     * @param string $configKey
     */
    public function __construct(string $configKey)
    {
        $this->ticket = $configKey;
    }
    
    public function getSupportedTypes(): array
    {
        // TODO: Return array of supported log types, such as Log::TYPE_DEBUG or Log::TYPE_ERROR
        return [
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
        return [];
    }
}