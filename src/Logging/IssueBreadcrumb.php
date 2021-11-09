<?php

namespace Utopia\Logging;

use Exception;

class IssueBreadcrumb
{
    /**
     * @var string (required, can be one of 'debug', 'error', 'info')
     */
    protected string $type;

    /**
     * @var string (required, for example 'auth')
     */
    protected string $category;

    /**
     * @var string (required, for example 'User is logged in')
     */
    protected string $message;

    /**
     * @var float (required, microtime as float)
     */
    protected float $timestamp;

    /**
     * IssueBreadcrumb constructor.
     *
     * @param string $type
     * @param string $category
     * @param string $message
     * @param string $level
     * @param float $timestamp
     */
    public function __construct(string $type, string $category, string $message, float $timestamp)
    {
        $this->type = $type;
        $this->category = $category;
        $this->message = $message;
        $this->timestamp = $timestamp;
    }
}