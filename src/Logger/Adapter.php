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
    abstract public static function getName(): string;


    /**
     * Push log to external provider
     *
     * @param Log $log
     * @return int
     * @throws Exception
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
}