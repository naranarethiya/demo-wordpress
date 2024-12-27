<?php

namespace GoDaddy\WordPress\MWC\Core\JobQueue\Contracts;

use GoDaddy\WordPress\MWC\Core\JobQueue\Events\QueuedJobDoneEvent;

/**
 * A job that can be queued.
 */
interface QueueableJobContract
{
    /**
     * Add an array of jobs (class names) to the queue.
     *
     * @param class-string<QueueableJobContract>[] $chain
     * @return $this
     */
    public function setChain(array $chain);

    /**
     * Add args to the job.
     *
     * @param ?mixed[] $args
     * @return $this
     */
    public function setArgs(array $args = null);

    /**
     * Execute the job.
     *
     * Typically, this should call {@see QueueableJobContract::jobDone()} when complete. However, if additional runs
     * or other processing is required, this method may not call complete the process.
     *
     * @return void
     */
    public function handle() : void;

    /**
     * Execute when the job has completed.
     *
     * The method broadcasts a {@see QueuedJobDoneEvent} ({@see QueueableJobTrait::jobDone()}).
     *
     * @return void
     */
    public function jobDone() : void;

    /**
     * Gets the job key, as defined in `config/queue.php`.
     *
     * @return string
     */
    public function getJobKey() : string;
}
