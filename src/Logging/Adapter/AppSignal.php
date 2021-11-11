<?php

namespace Utopia\Logging\Adapter;

use Utopia\Logging\Adapter;
use Utopia\Logging\Issue;

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
    public function getAdapterName(): string
    {
        return "appsignal";
    }

    /**
     * Push issue to external provider
     *
     * @param Issue $issue
     * @return int
     */
    public function pushIssue(Issue $issue): int
    {
        $params = [];

        foreach($issue->getExtra() as $paramKey => $paramValue) {
            $params[$paramKey] = var_export($paramValue, true);
        }

        $breadcrumbsObject = $issue->getBreadcrumbs();
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

        foreach($issue->getTags() as $tagKey => $tagValue) {
            $tags[$tagKey] = $tagValue;
        }

        if(!empty($issue->getType())) {
            $tags['type'] = $issue->getType();
        }
        if(!empty($issue->getUser()->getId())) {
            $tags['userId'] = $issue->getUser()->getId();
        }
        if(!empty($issue->getUser()->getUsername())) {
            $tags['userName'] = $issue->getUser()->getUsername();
        }
        if(!empty($issue->getUser()->getEmail())) {
            $tags['userEmail'] = $issue->getUser()->getEmail();
        }

        $requestBody = [
            'timestamp'=> \intval($issue->getTimestamp()),
            'namespace'=> $issue->getLogger(),
            'error'=> [
                'name'=> $issue->getMessage(),
                'message'=> $issue->getMessage(),
                'backtrace'=> []
            ],
            'environment'=> [
                'environment'=> $issue->getEnvironment(),
                'server'=> $issue->getServer(),
                'version'=> $issue->getVersion(),
            ],
            'revision'=> $issue->getVersion(),
            'action'=> $issue->getAction(),
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

        // also get the error and response code
        $errors = curl_error($ch);
        $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return $response;
    }

    /**
     * AppSignal constructor.
     *
     * @param string $apiKey
     */
    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }
}