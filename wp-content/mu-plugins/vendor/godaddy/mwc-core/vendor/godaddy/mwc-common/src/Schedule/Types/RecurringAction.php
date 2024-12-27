<?php

namespace GoDaddy\WordPress\MWC\Common\Schedule\Types;

use DateInterval;
use GoDaddy\WordPress\MWC\Common\Schedule\Contracts\SchedulableContract;
use GoDaddy\WordPress\MWC\Common\Schedule\Exceptions\InvalidScheduleException;
use GoDaddy\WordPress\MWC\Common\Schedule\Schedule;

/**
 * Recurring action to schedule.
 */
class RecurringAction extends Schedule implements SchedulableContract
{
    /** @var DateInterval recurrence of the schedule */
    protected $interval;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->setType('recurring');
    }

    /**
     * Sets the recurring interval the action should be scheduled for.
     *
     * @param DateInterval $value
     * @return $this
     */
    public function setInterval(DateInterval $value) : RecurringAction
    {
        $this->interval = $value;

        return $this;
    }

    /**
     * Gets the schedule interval in seconds.
     *
     * @return int
     */
    protected function getIntervalTimestamp() : int
    {
        $startTime = $this->scheduleAt;
        $nextTime = (clone $this->scheduleAt)->add($this->interval);

        return $nextTime->getTimestamp() - $startTime->getTimestamp();
    }

    /**
     * Validates the action schedule.
     *
     * @param string $validationContext verb used internally to build more meaningful exception messages
     * @return void
     * @throws InvalidScheduleException
     */
    public function validate(string $validationContext = 'handle') : void
    {
        parent::validate($validationContext);

        if (! $this->interval instanceof DateInterval) {
            throw new InvalidScheduleException(sprintf('Cannot %1$s a %2$s action: the schedule interval is not specified.', $validationContext, $this->getType()));
        }
    }

    /**
     * Schedules the recurring action.
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

        as_schedule_recurring_action(
            $this->scheduleAt->getTimestamp(),
            $this->getIntervalTimestamp(),
            $this->name,
            $this->arguments,
            $this->collectionName,
            $this->unique,
            $this->priority
        );
    }
}
