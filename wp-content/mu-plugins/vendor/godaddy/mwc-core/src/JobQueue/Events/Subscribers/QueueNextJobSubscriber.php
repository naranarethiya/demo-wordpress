<?php

namespace GoDaddy\WordPress\MWC\Core\JobQueue\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Schedule\Exceptions\InvalidScheduleException;
use GoDaddy\WordPress\MWC\Core\JobQueue\Contracts\QueueableJobContract;
use GoDaddy\WordPress\MWC\Core\JobQueue\Events\QueuedJobCreatedEvent;
use GoDaddy\WordPress\MWC\Core\JobQueue\Events\QueuedJobDoneEvent;
use GoDaddy\WordPress\MWC\Core\JobQueue\Exceptions\UnregisteredJobException;
use GoDaddy\WordPress\MWC\Core\JobQueue\Services\ScheduledJobQueueDispatchService;

/**
 * Subscriber for {@see QueuedJobCreatedEvent} and {@see QueuedJobDoneEvent}.
 * This is responsible for dispatching the next job in the chain.
 */
class QueueNextJobSubscriber implements SubscriberContract
{
    protected ScheduledJobQueueDispatchService $dispatchService;

    /**
     * Constructor.
     *
     * @param ScheduledJobQueueDispatchService $dispatchService
     */
    public function __construct(ScheduledJobQueueDispatchService $dispatchService)
    {
        $this->dispatchService = $dispatchService;
    }

    /**
     * Handles the event.
     *
     * @param EventContract $event
     * @return void
     * @throws InvalidScheduleException|Exception
     */
    public function handle(EventContract $event)
    {
        if (! $event instanceof QueuedJobDoneEvent && ! $event instanceof QueuedJobCreatedEvent) {
            return;
        }

        $nextJobClass = array_shift($event->chained);

        if (empty($nextJobClass)) {
            return;
        }

        $this->dispatchNextJob($nextJobClass, $event->chained, $event->args, $event->withOverlapping);
    }

    /**
     * Dispatches the next job in the chain.
     *
     * @param class-string<QueueableJobContract> $nextJobClass
     * @param class-string<QueueableJobContract>[] $chain
     * @param ?array<mixed> $args
     * @param bool $withOverlapping
     * @return void
     * @throws InvalidScheduleException
     * @throws UnregisteredJobException
     */
    protected function dispatchNextJob(string $nextJobClass, array $chain, array $args = null, bool $withOverlapping = true) : void
    {
        $this->dispatchService->setOverlapping($withOverlapping)->dispatch($nextJobClass, $chain, $args);
    }
}
