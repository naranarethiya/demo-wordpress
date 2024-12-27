<?php

namespace GoDaddy\WordPress\MWC\Core\JobQueue\Services;

use DateTime;
use GoDaddy\WordPress\MWC\Common\Schedule\Exceptions\InvalidScheduleException;
use GoDaddy\WordPress\MWC\Common\Schedule\Schedule;
use GoDaddy\WordPress\MWC\Core\JobQueue\Contracts\QueueableJobContract;
use GoDaddy\WordPress\MWC\Core\JobQueue\Exceptions\UnregisteredJobException;
use GoDaddy\WordPress\MWC\Core\JobQueue\Helpers\JobConfigHelper;

/**
 * Service to dispatch a scheduled job to process the next job in a chain.
 */
class ScheduledJobQueueDispatchService
{
    public const ACTION_SCHEDULER_JOB_NAME = 'mwc_gd_process_background_job';

    /**
     * @var bool when set to true allows the scheduled job to be dispatched even if it is already scheduled.
     */
    protected bool $withOverlapping = true;

    /**
     * @param bool $withOverlapping
     * @return $this
     */
    public function setOverlapping(bool $withOverlapping) : ScheduledJobQueueDispatchService
    {
        $this->withOverlapping = $withOverlapping;

        return $this;
    }

    /**
     * Dispatches a scheduled job to process the next job in a chain.
     *
     * @param class-string<QueueableJobContract> $nextJobClass class name of the job to be dispatched
     * @param class-string<QueueableJobContract>[] $chain name of other jobs in the chain, to be processed after this job completes
     * @param ?array<mixed> $args
     * @return void
     * @throws InvalidScheduleException
     * @throws UnregisteredJobException
     */
    public function dispatch(string $nextJobClass, array $chain, array $args = null) : void
    {
        $action = Schedule::singleAction()
            ->setName(static::ACTION_SCHEDULER_JOB_NAME)
            ->setArguments(JobConfigHelper::getJobKeyByClassName($nextJobClass), JobConfigHelper::convertJobClassNamesToKeys($chain), $args)
            ->setScheduleAt(new DateTime('now'));

        if (! $this->withOverlapping && $action->isScheduled()) {
            return;
        }

        $action->schedule();
    }
}
