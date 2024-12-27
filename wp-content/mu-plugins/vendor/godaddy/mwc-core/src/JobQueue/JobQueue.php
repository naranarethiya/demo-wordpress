<?php

namespace GoDaddy\WordPress\MWC\Core\JobQueue;

use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\JobQueue\Contracts\QueueableJobContract;
use GoDaddy\WordPress\MWC\Core\JobQueue\Events\QueuedJobCreatedEvent;
use GoDaddy\WordPress\MWC\Core\JobQueue\Services\ScheduledJobQueueDispatchService;

/**
 * Class to set up a new job queue.
 */
class JobQueue
{
    use CanGetNewInstanceTrait;

    /** @var class-string<QueueableJobContract>[] */
    protected array $chained;

    /** @var bool should the dispatcher allow duplicate jobs. */
    protected bool $withOverlapping = true;

    /**
     * Configures a chain of jobs. Once dispatched ({@see static::dispatch()}), the job chain will be run sequentially.
     *
     * @param class-string<QueueableJobContract>[] $chained Names of the job classes to chain. Jobs should be registered in `queue.jobs` config.
     * @return $this
     */
    public function chain(array $chained) : JobQueue
    {
        $this->chained = $chained;

        return $this;
    }

    /**
     * Disables overlapping job dispatch.
     *
     * By disabling overlapping, the dispatcher only dispatches the job if it is not already scheduled
     * {@see ScheduledJobQueueDispatchService::dispatch()}. The scheduler queries scheduled status by examining all
     * the arguments passed to the scheduled job, i.e. it matches the exact order of `$chained` jobs as well
     * as the `$args` passed via `dispatch()`.
     *
     * @return $this
     */
    public function withoutOverlapping() : JobQueue
    {
        $this->withOverlapping = false;

        return $this;
    }

    /**
     * Dispatches the chained jobs.
     *
     * Jobs are completed asynchronously.
     *
     * @param ?mixed[] $args
     * @return void
     */
    public function dispatch(array $args = null) : void
    {
        Events::broadcast(QueuedJobCreatedEvent::getNewInstance($this->chained, $args, $this->withOverlapping));
    }
}
