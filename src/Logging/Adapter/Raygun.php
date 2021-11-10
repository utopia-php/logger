<?php

namespace Utopia\Logging\Adapter;

use Utopia\Logging\Adapter;
use Utopia\Logging\Issue;

class Raygun extends Adapter
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
    public function getAdapterName(): string
    {
        return "raygun";
    }

    /**
     * Push issue to external server
     *
     * @param Issue $issue
     * @return int
     */
    public function pushIssue(Issue $issue): int
    {
        $breadcrumbsObject = $issue->getBreadcrumbs();
        $breadcrumbsArray = [];

        foreach ($breadcrumbsObject as $breadcrumb) {
            \array_push($breadcrumbsArray, [
                'category' => $breadcrumb->getCategory(),
                'message' => $breadcrumb->getMessage(),
                'type' => $breadcrumb->getType(),
                'level' => $breadcrumb->getType(),
                'timestamp' =>\intval($breadcrumb->getTimestamp())
            ]);
        }

        $tagsArray = [];

        foreach($issue->getTags() as $tagKey => $tagValue) {
            \array_push($tagsArray, $tagKey . ': ' . $tagValue);
        }

        \array_push($tagsArray, 'type: ' . $issue->getType());
        \array_push($tagsArray, 'environment: ' . $issue->getEnvironment());

        // prepare issue (request body)
        $requestBody = [
            'occurredOn' =>  \intval($issue->getTimestamp()),
            'details' => [
                'machineName' => $issue->getServer(),
                'groupingKey' => $issue->getLogger(),
                'version' => $issue->getVersion(),
                'error' => [
                    'className' => $issue->getAction(),
                    'message' => $issue->getMessage()
                ],
                'tags' => $tagsArray,
                'userCustomData' => $issue->getExtra(),
                'user' => [
                    'isAnonymous' => empty($issue->getUser()),
                    'identifier' => $issue->getUser()->getId(),
                    'email' => $issue->getUser()->getEmail(),
                    'fullName' => $issue->getUser()->getUsername(),
                ],
                'breadcrumbs' => $breadcrumbsArray
            ]
        ];

        // init curl object
        $ch = curl_init();

        // define options
        $optArray = array(
            CURLOPT_URL => 'https://api.raygun.com/entries',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => \json_encode($requestBody),
            CURLOPT_HEADEROPT => CURLHEADER_UNIFIED,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'X-ApiKey: ' . $this->apiKey)
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
     * Raygun constructor.
     *
     * @param string $apiKey
     */
    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }
}