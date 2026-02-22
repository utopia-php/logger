<?php

namespace Utopia\Logger\Exception;

use Exception;

class Push extends Exception
{
    /**
     * @param  string  $message  Human-readable reason for the push failure (e.g. "Sentry push failed with status code 429: ...")
     * @param  int  $statusCode  HTTP status code to return (e.g. 429 or 500)
     */
    public function __construct(string $message, int $statusCode = 500)
    {
        parent::__construct($message, $statusCode);
    }

    public function getStatusCode(): int
    {
        return (int) $this->getCode();
    }
}
