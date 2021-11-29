<?php

namespace Utopia\Logger\Adapter;

use Exception;
use Utopia\Logger\Adapter;
use Utopia\Logger\Log;
use Utopia\Logger\Logger;
use function curl_close;
use function curl_error;
use function curl_exec;
use function curl_getinfo;
use const CURLINFO_HTTP_CODE;

// Reference Material
// https://docs.appsignal.com/api/public-endpoint/errors.html

class AppSignal extends Adapter
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
        return "appSignal";
    }

    /**
     * Push log to external provider
     *
     * @param Log $log
     * @return int
     * @throws Exception
     */
    public function pushLog(Log $log): int
    {
        $params = [];

        foreach($log->getExtra() as $paramKey => $paramValue) {
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
                    'type' => $breadcrumb->getType()
                ]
            ]);
        }

        $tags = array();

        foreach($log->getTags() as $tagKey => $tagValue) {
            $tags[$tagKey] = $tagValue;
        }

        if(!empty($log->getType())) {
            $tags['type'] = $log->getType();
        }
        if(!empty($log->getUser()) &&  !empty($log->getUser()->getId())) {
            $tags['userId'] = $log->getUser()->getId();
        }
        if(!empty($log->getUser()) &&  !empty($log->getUser()->getUsername())) {
            $tags['userName'] = $log->getUser()->getUsername();
        }
        if(!empty($log->getUser()) &&  !empty($log->getUser()->getEmail())) {
            $tags['userEmail'] = $log->getUser()->getEmail();
        }

        $tags['sdk'] = 'utopia-logger/' . Logger::LIBRARY_VERSION;

        $requestBody = [
            'timestamp'=> \intval($log->getTimestamp()),
            'namespace'=> $log->getNamespace(),
            'error'=> [
                'name'=> $log->getMessage(),
                'message'=> $log->getMessage(),
                'backtrace'=> []
            ],
            'environment'=> [
                'environment'=> $log->getEnvironment(),
                'server'=> $log->getServer(),
                'version'=> $log->getVersion(),
            ],
            'revision'=> $log->getVersion(),
            'action'=> $log->getAction(),
            'params'=> $params,
            'tags'=> $tags,
            'breadcrumbs' => $breadcrumbsArray
        ];

        // init curl object
        $ch = curl_init();

        // define options
        $optArray = array(
            CURLOPT_URL => 'https://appsignal-endpoint.net/collect?api_key=' . $this->apiKey . '&version=1.3.19',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => \json_encode($requestBody),
            CURLOPT_HEADEROPT => CURLHEADER_UNIFIED,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json')
        );

        // apply those options
        curl_setopt_array($ch, $optArray);

        // execute request and get response
        $result = curl_exec($ch);
        $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if(!$result && $response >= 400) {
            throw new Exception("Log could not be pushed with status code " . $response . ": " . curl_error($ch));
        }

        curl_close($ch);

        return $response;
    }

    /**
     * AppSignal constructor.
     *
     * @param string $configKey
     */
    public function __construct(string $configKey)
    {
        $this->apiKey = $configKey;
    }

    public function validateLog(Log $log): bool
    {
        // Supports all log types (as tag)
        switch ($log->getType()) {
            case Log::TYPE_INFO:
            case Log::TYPE_DEBUG:
            case Log::TYPE_VERBOSE:
            case Log::TYPE_ERROR:
            case Log::TYPE_WARNING:
                break;
            default:
                throw new Exception("Supported log types for this adapter are: TYPE_INFO, TYPE_DEBUG, TYPE_VERBOSE, TYPE_ERROR, TYPE_WARNING");
        }

        // Support all breadcrumb types (as metadata)
        foreach($log->getBreadcrumbs() as $breadcrumb) {
            switch ($breadcrumb->getType()) {
                case Log::TYPE_INFO:
                case Log::TYPE_DEBUG:
                case Log::TYPE_VERBOSE:
                case Log::TYPE_ERROR:
                case Log::TYPE_WARNING:
                    break;
                default:
                    throw new Exception("Supported breadcrumb types for this adapter are: TYPE_INFO, TYPE_DEBUG, TYPE_VERBOSE, TYPE_ERROR, TYPE_WARNING");
            }
        }

        // Support all environment types (as key-value pair)
        switch ($log->getEnvironment()) {
            case Log::ENVIRONMENT_STAGING:
            case Log::ENVIRONMENT_PRODUCTION:
                break;
            default:
                throw new Exception("Supported environments for this adapter are: ENVIRONMENT_STAGING, ENVIRONMENT_PRODUCTION");
        }

        return true;
    }
}