<?php

namespace Utopia\Logging;

abstract class Adapter
{
    /**
     * Get unique name of an adapter
     *
     * @return string
     */
    abstract public function getAdapterName(): string;


    /**
     * Push issue to external provider
     *
     * @param Issue $issue
     * @return int
     */
    abstract public function pushIssue(Issue $issue): int;
}