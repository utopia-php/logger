<?php

namespace Utopia\Logger;

use Exception;

class Logger
{
    const LIBRARY_VERSION = "0.1.0";
    /**
     * @var string[]
     */
    static protected array $providerNames = array();

    /**
     * @var Adapter
     */
    protected Adapter $adapter;


    /**
     * Logger constructor.
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
     * @throws Exception
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

        if($this->adapter->validateLog($log)) {
            // Push log
            return $this->adapter->pushLog($log);
        }

        return 500;
    }

    /**
     * Get list of available providers
     *
     * @param string $providerName
     * @return void
     */
    static public function registerProvider(string $providerName): void {
        \array_push(Logger::$providerNames, $providerName);
    }

    /**
     * Get list of available providers
     *
     * @return int[]
     */
    static public function getProviders(): array {
        return Logger::$providerNames;
    }

    /**
     * Check if provider is available
     *
     * @param string $providerName
     * @return bool
     */
    static public function hasProvider(string $providerName): bool {
        foreach (Logger::$providerNames as $registeredProviderName) {
            if($registeredProviderName === $providerName) {
                return true;
            }
        }

        return false;
    }
}