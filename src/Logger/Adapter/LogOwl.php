<?php

namespace Utopia\Logger\Adapter;

use Exception;
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
     * Return unique adapter name
     *
     * @return string
     */
    public static function getName(): string
    {
        return "logOwl";
    }

    /**
     * Return adapter type
     *
     * @return string
     */
    public static function getAdapterType():string
    {
        return "php";
    }

    /**
     * Return adapter version
     *
     * @return string
     */
    public static function getAdapterVersion():string
    {
        return "1.0";
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
                'environment'=> $log->getEnvironment(),
                'namespace' => $log->getNamespace(),
                'version' => $log->getVersion(),
                'message' => $log->getMessage(),
                'id' => $id,
                '$email' => $email,
                '$username' => $username
            ],
            'type' => $log->getType(),
            'metrics'=> [
                'platform' => $log->getServer()
            ],
            'logs'=> $breadcrumbsArray,
            'timestamp' => \intval($log->getTimestamp()),
            'adapter' => [
                'name' => $this->getName(),
                'type' => $this->getAdapterType(),
                'version' => $this->getAdapterVersion()
            ]
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
     * LogOwl constructor.
     *
     * @param string $configKey
     */
    public function __construct(string $configKey)
    {
        $this->ticket = $configKey;
    }
    
    public function getSupportedTypes(): array
    {
        return [
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
            Log::TYPE_WARNING,
            Log::TYPE_ERROR
        ];
    }
}