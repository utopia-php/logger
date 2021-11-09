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
     * Store new issue. Currently, it is instantly pushed to Adapter, but in future it could pool to increase performance.
     *
     * @param Issue $issue
     * @return void
     */
    public function addIssue(Issue $issue): void {
        // Validate issue
        if(
            empty($issue->getAction()) ||
            empty($issue->getEnvironment()) ||
            empty($issue->getMessage()) ||
            empty($issue->getType()) ||
            empty($issue->getVersion())
        ) {
            throw new Exception('Issue is not ready to be pushed.');
        }

        // Push issue
        $this->adapter->pushIssue($issue);
    }
}