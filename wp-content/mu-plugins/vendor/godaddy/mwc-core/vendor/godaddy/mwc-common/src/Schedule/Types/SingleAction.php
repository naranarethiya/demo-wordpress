<?php

namespace GoDaddy\WordPress\MWC\Common\Schedule\Types;

use GoDaddy\WordPress\MWC\Common\Schedule\Contracts\SchedulableContract;
use GoDaddy\WordPress\MWC\Common\Schedule\Exceptions\InvalidScheduleException;
use GoDaddy\WordPress\MWC\Common\Schedule\Schedule;

/**
 * Single action to schedule once.
 */
class SingleAction extends Schedule implements SchedulableContract
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->setType('single');
    }

    /**
     * Schedules the single action to be run once.
     *
     * @return void
     * @throws InvalidScheduleException
     */
    public function schedule() : void
    {
        $this->validate('schedule');

        if (! $this->shouldSchedule()) {
            return;
        }

        as_schedule_single_action(
            $this->scheduleAt->getTimestamp(),
            $this->name,
            $this->arguments,
            $this->collectionName,
            $this->unique,
            $this->priority
        );
    }
}
