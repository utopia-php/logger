<?php

namespace Utopia\Logger\Log;

use Exception;

class Breadcrumb
{
    const TYPE_DEBUG = "debug";
    const TYPE_ERROR = "error";
    const TYPE_INFO = "info";

    /**
     * @var string (required, can be one of 'TYPE_DEBUG', 'TYPE_ERROR', 'TYPE_INFO')
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
     * Breadcrumb constructor.
     *
     * @param string $type
     * @param string $category
     * @param string $message
     * @param float $timestamp
     */
    public function __construct(string $type, string $category, string $message, float $timestamp)
    {
        $this->type = $type;
        $this->category = $category;
        $this->message = $message;
        $this->timestamp = $timestamp;

        switch ($this->getType()) {
            case self::TYPE_DEBUG:
            case self::TYPE_ERROR:
            case self::TYPE_INFO:
                break;
            default:
                throw new Exception("Type has to be one of TYPE_DEBUG, TYPE_ERROR, TYPE_INFO.");
        }
    }

    /**
     * Get breadcrumb type
     *
     * @return string
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * Get breadcrumb category
     *
     * @return string
     */
    public function getCategory(): string {
        return $this->category;
    }

    /**
     * Get breadcrumb message
     *
     * @return string
     */
    public function getMessage(): string {
        return $this->message;
    }

    /**
     * Get breadcrumb timestamp
     *
     * @return float
     */
    public function getTimestamp(): float {
        return $this->timestamp;
    }

}