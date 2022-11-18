<?php

namespace Utopia\Logger\Adapter;

use Exception;
use Utopia\Logger\Adapter;
use Utopia\Logger\Log;

// Reference Material
// https://docs.newrelic.com/docs/logs/log-api/introduction-log-api

class NewRelic extends Adapter
{
    /**
     * @var string (required, can be found in NewRelic -> Account dropdown -> API keys)
     */
    protected string $licenseKey;

    /**
     * @var string (optional, the api url where New Relic is reachable, in case of an EU New Relic account could
     *              is 'https://log-api.eu.newrelic.com/log/v1'. defaults to 'https://log-api.newrelic.com/log/v1')
     */
    private string $apiUrl = 'https://log-api.newrelic.com/log/v1';

    /**
     * Return unique adapter name
     *
     * @return string
     */
    public static function getName(): string
    {
        return "newRelic";
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
        $params = [];
        foreach ($log->getExtra() as $paramKey => $paramValue) {
            $params[$paramKey] = var_export($paramValue, true);
        }

        $breadcrumbsArray = [];
        foreach ($log->getBreadcrumbs() as $breadcrumb) {
            $breadcrumbsArray[] = [
                'timestamp' => (int) $breadcrumb->getTimestamp(),
                'category' => $breadcrumb->getCategory(),
                'action' => $breadcrumb->getMessage(),
                'metadata' => [
                    'type' => $breadcrumb->getType(),
                ],
            ];
        }

        $tags = [];
        foreach ($log->getTags() as $tagKey => $tagValue) {
            $tags[$tagKey] = $tagValue;
        }

        $requestBody = [
            'timestamp' => (int) $log->getTimestamp(),
            'message' => $log->getMessage(),
            'logtype' => $log->getType(),
            'version' => $log->getVersion(),
            'environment' => $log->getEnvironment(),
            'action' => $log->getAction(),
            'namespace' => $log->getNamespace(),
            'server' => $log->getServer(),
            'user' => [
                'id' => $log->getUser()->getId(),
                'email' => $log->getUser()->getEmail(),
                'username' => $log->getUser()->getUsername(),
            ],
            'breadcrumbs' => $breadcrumbsArray,
            'tags' => $tags,
            'params' => $params,
        ];

        if (!empty($log->getUser())) {
            $requestBody['user'] = [];

            if (!empty($log->getUser()->getId())) {
                $requestBody['user']['id'] = $log->getUser()->getId();
            }

            if (!empty($log->getUser()->getUsername())) {
                $requestBody['user']['username'] = $log->getUser()->getUsername();
            }

            if (!empty($log->getUser()->getEmail())) {
                $requestBody['user']['email'] = $log->getUser()->getEmail();
            }
        }

        // init curl object
        $ch = curl_init();

        // define options
        $optArray = [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($requestBody),
            CURLOPT_HEADEROPT => CURLHEADER_UNIFIED,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Api-Key: ' . $this->licenseKey,
            ],
        ];

        // apply those options
        curl_setopt_array($ch, $optArray);

        // execute request and get response
        $result = curl_exec($ch);
        $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (!$result && $response >= 400) {
            throw new Exception("Log could not be pushed with status code " . $response . ": " . curl_error($ch));
        }

        curl_close($ch);

        return $response;
    }

    /**
     * NewRelic constructor.
     *
     * @param string $configKey
     */
    public function __construct(string $configKey)
    {
        $configChunks = explode(';', $configKey);
        $this->licenseKey = $configChunks[0];

        if (!empty($configChunks[1])) {
            $this->apiUrl = $configChunks[1];
        }
    }

    /**
     * @return string[]
     */
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

    /**
     * @return string[]
     */
    public function getSupportedEnvironments(): array
    {
        return [
            Log::ENVIRONMENT_STAGING,
            Log::ENVIRONMENT_PRODUCTION,
        ];
    }

    /**
     * @return string[]
     */
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
