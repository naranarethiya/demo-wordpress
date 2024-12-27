<?php

namespace GoDaddy\WordPress\MWC\Core\JobQueue\Traits;

use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Core\JobQueue\Contracts\QueueableJobContract;
use GoDaddy\WordPress\MWC\Core\JobQueue\Events\QueuedJobDoneEvent;

/**
 * Trait for queueable jobs, to implement common methods in the {@see QueueableJobContract} interface.
 */
trait QueueableJobTrait
{
    /** @var class-string<QueueableJobContract>[] chain of job class names, in the order they should be run */
    protected array $chain;

    /** @var ?mixed[] optional arguments for the current job; these values may change per job run (example: an offset for a query, a batch of IDs to process, etc.) */
    protected ?array $args = null;

    /**
     * Add an array of jobs (class names) to the queue.
     *
     * @param class-string<QueueableJobContract>[] $chain
     * @return $this
     */
    public function setChain(array $chain)
    {
        $this->chain = $chain;

        return $this;
    }

    /**
     * @param ?array<mixed> $args
     * @return $this
     */
    public function setArgs(?array $args = null)
    {
        $this->args = $args;

        return $this;
    }

    /**
     * Sets the chain to the current job class.
     *
     * This is useful for things like batch jobs where an array of items is passed in, and the job needs to be re-queued
     * until all the items in the array have been processed.
     *
     * @return $this
     */
    public function reQueueJob()
    {
        array_unshift($this->chain, get_class($this));

        return $this;
    }

    /**
     * Job processed successfully.
     *
     * @return void
     */
    public function jobDone() : void
    {
        Events::broadcast(QueuedJobDoneEvent::getNewInstance($this->chain, $this->args));
    }
}
