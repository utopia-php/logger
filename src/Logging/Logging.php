<?php

namespace Utopia\Logging;

use Exception;

class Logging
{
    /**
     * @var Adapter
     */
    protected Adapter $adapter;

    /**
     * Logging constructor.
     *
     * @param Adapter $adapter
     */
    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Store new log. Currently, it is instantly pushed to Adapter, but in future it could pool to increase performance.
     *
     * @param Log $log
     * @return int
     */
    public function addLog(Log $log): int {
        // Validate log
        if(
            empty($log->getAction()) ||
            empty($log->getEnvironment()) ||
            empty($log->getMessage()) ||
            empty($log->getType()) ||
            empty($log->getVersion())
        ) {
            throw new Exception('Log is not ready to be pushed.');
        }

        // Push log
        return $this->adapter->pushLog($log);
    }
}