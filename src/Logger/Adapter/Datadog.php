<?php

namespace Utopia\Logger\Adapter;

use Utopia\Logger\Adapter;
use Utopia\Logger\Log;
use Utopia\Logger\Logger;

class Datadog extends Adapter
{
    private $apiKey;
    private $apiEndpoint;

    public static function getName(): string
    {
        return "datadog";
    }

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->apiEndpoint = "https://http-intake.logs.datadoghq.com/v1/input/{$this->apiKey}";
    }

    public function push(Log $log): int
    {
        $data = [
            "message" => $log->getMessage(),
            "level" => $log->getLevel(),
            "timestamp" => $log->getTimestamp(),
            "tags" => $log->getTags(),
            // Add any additional fields specific to Datadog logging
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiEndpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        if (curl_errno($ch)) {
            throw new \Exception('Error pushing log to Datadog: ' . curl_error($ch));
        }

        curl_close($ch);

        return $statusCode;
    }

    public function getSupportedTypes(): array
    {
        return [
            Log::TYPE_DEBUG,
            Log::TYPE_INFO,
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
            Log::TYPE_WARNING,
            Log::TYPE_ERROR,
        ];
    }
}
