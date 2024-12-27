<?php

namespace GoDaddy\WordPress\MWC\Core\JobQueue\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Core\JobQueue\Contracts\BatchJobContract;
use GoDaddy\WordPress\MWC\Core\JobQueue\DataObjects\BatchJobOutcome;
use GoDaddy\WordPress\MWC\Core\JobQueue\DataObjects\BatchJobSettings;

/**
 * Trait for batch jobs. Implements common methods in {@see BatchJobContract}.
 *
 * @method BatchJobSettings getJobSettings()
 */
trait BatchJobTrait
{
    use QueueableJobTrait;
    use HasJobSettingsTrait;

    /**
     * @var int Number of resources attempted to process -- this should be how many results were found in the query for this batch
     */
    protected int $attemptedResourcesCount = 0;

    /**
     * Sets the number of resources that were attempted to be processed in this batch.
     *
     * @param int $value
     * @return $this
     */
    public function setAttemptedResourcesCount(int $value) : BatchJobContract
    {
        $this->attemptedResourcesCount = $value;

        return $this;
    }

    /**
     * Gets the number of resources that were attempted to be processed in this batch. (Usually the result of how
     * many items were found in a query.).
     *
     * @return int
     */
    public function getAttemptedResourcesCount() : int
    {
        return $this->attemptedResourcesCount;
    }

    /**
     * Handles the job.
     *
     * This processes the current batch of items and then handles any cleanup operations required when the entire
     * resource batch is complete.
     *
     * @return void
     * @throws Exception
     */
    public function handle() : void
    {
        $outcome = $this->processBatch();

        if ($outcome->isComplete) {
            $this->onAllBatchesCompleted();
        } else {
            /*
             * We're not yet done processing this resource, so we'll add the current job back to the start of the chain.
             * This means that the current job will run again and we won't yet proceed to the next resource.
             */
            $this->reQueueJob();
        }

        $this->jobDone();
    }

    /**
     * Performs any required actions when all batches have been successfully completed, and the job is fully done.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    protected function onAllBatchesCompleted() : void
    {
        // no-op
    }

    /**
     * Processes a single batch.
     *
     * @return BatchJobOutcome
     * @throws Exception
     */
    abstract protected function processBatch() : BatchJobOutcome;

    /**
     * Makes a {@see BatchJobOutcome} DTO with an accurate `$isComplete` property, based on the number of items found in the current batch.
     *
     * @return BatchJobOutcome
     */
    protected function makeOutcome() : BatchJobOutcome
    {
        return BatchJobOutcome::getNewInstance([
            // we're all done if we just retrieved fewer resources than we asked for
            'isComplete' => $this->getAttemptedResourcesCount() < $this->getJobSettings()->maxPerBatch,
        ]);
    }
}
