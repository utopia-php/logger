<?php

namespace Utopia\Logger;

use Exception;

abstract class Adapter
{
    /**
     * Get unique name of an adapter
     *
     * @return string
     */
    abstract public function getAdapterName(): string;


    /**
     * Push log to external provider
     *
     * @param Log $log
     * @return int
     */
    abstract public function pushLog(Log $log): int;


    /**
     * Validate if a log is properly configured for specific adapter
     *
     * @param Log $log
     * @return bool
     * @throws Exception
     */
    abstract public function validateLog(Log $log): bool;

    public function __construct(string $apiKey)
    {
        Logger::registerProvider($this->getAdapterName());
    }
}