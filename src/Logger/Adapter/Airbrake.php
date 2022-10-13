<?php

namespace Utopia\Logger\Adapter;

use Exception;
use Utopia\Logger\Adapter;
use Utopia\Logger\Log;


/**
 * Reference Docs:
 * https://docs.airbrake.io/docs/devops-tools/api/#create-notice-v3
 * 
 * https://docs.airbrake.io/docs/devops-tools/api/#post-data-fields-v3
 */
class Airbrake extends Adapter
{
  /**
   * Base URL for airbrake api endpoint
   */
  private string $airbrakeAPIHost;

  /**
   * API endpoint for pushing error data into airbrake (creating notice)
   */
  private string $airbrakeCreateNoticeURL;

  /**
   * Required to access airbrake API
   * 
   * Ref: https://docs.airbrake.io/docs/devops-tools/api/
   */
  protected string $projectKey;

  /**
   * Project unique ID
   * 
   * Ref: https://docs.airbrake.io/docs/devops-tools/api/
   */
  protected string $projectId;

  /**
   * Airbrake constructor
   * 
   * @param string $configKey Contains projectId and projectKey
   */
  public function __construct(string $configKey)
  {
    $this->airbrakeAPIHost = 'https://api.airbrake.io';

    $configChunks = \explode(';', $configKey);
    $this->projectId = $configChunks[0];
    $this->projectKey = $configChunks[1];

    $this->airbrakeCreateNoticeURL = $this->airbrakeAPIHost . "/api/v3/projects/" . $this->projectId . "/notices?key=" . $this->projectKey;
  }

  /**
   * Unique adapter name
   * 
   * @return string
   */
  public static function getName(): string
  {
    return "airbrake";
  }

  /**
   * Push log to airbrake
   * 
   * @param Log $log
   * @return int Response status code
   * @throws Exception When status code is >= 400
   */
  public function push(Log $log): int
  {
    $breadcrumbObjects = $log->getBreadcrumbs();
    $userLog = $log->getUser();
    $user = [
      "id" => $userLog->getId(),
      "name" => $userLog->getUsername(),
      "email" => $userLog->getEmail()
    ];
    $context = [
      "hostname" => $log->getServer(),
      "environment" => $log->getEnvironment(),
      "severity" => $log->getType(),
      "version" => $log->getVersion(),
      "user" => $user,
      "action" => $log->getAction()
    ];
    $errors = [];

    foreach ($breadcrumbObjects as $breadcrumbObject) {
      $error = [
        "type" => $breadcrumbObject->getType(),
        "message" => $breadcrumbObject->getMessage()
      ];

      \array_push($errors, $error);
    }

    $requestBody = [
      "errors" => $errors,
      "context" => $context,
      'lastNoticeAt' => $log->getTimestamp()
    ];

    $curlHandler = \curl_init();

    $options = array(
      CURLOPT_URL => $this->airbrakeCreateNoticeURL,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => \json_encode($requestBody),
      CURLOPT_HEADEROPT => \CURLHEADER_UNIFIED,
      CURLOPT_HTTPHEADER => array('Content-Type: application/json')
    );

    \curl_setopt_array($curlHandler, $options);

    $result = curl_exec($curlHandler);

    $response = curl_getinfo($curlHandler, \CURLINFO_HTTP_CODE);

    if ($result && $response >= 400) {
      throw new Exception("Log could not be pushed with status code " . $response . ": " . \curl_error($curlHandler));
    }

    \curl_close($curlHandler);

    return $response;
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
      Log::TYPE_DEBUG,
      Log::TYPE_WARNING,
      Log::TYPE_ERROR
    ];
  }
}
