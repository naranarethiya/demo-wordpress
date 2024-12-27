<?php

namespace GoDaddy\WordPress\MWC\Core\JobQueue\Contracts;

/**
 * A job that processes items in batches (the same job may be executed multiple times as it batches items).
 */
interface BatchJobContract
{
    /**
     * Sets the number of resources that were attempted to be processed in this batch.
     *
     * @param int $value
     * @return $this
     */
    public function setAttemptedResourcesCount(int $value) : BatchJobContract;

    /**
     * Gets the number of resources that were attempted to be processed in this batch. (Usually the result of how
     * many items were found in a query.).
     *
     * @return int
     */
    public function getAttemptedResourcesCount() : int;
}
