<?php

namespace GoDaddy\WordPress\MWC\Common\Schedule;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Schedule\Exceptions\InvalidScheduleException;
use GoDaddy\WordPress\MWC\Common\Schedule\Types\RecurringAction;
use GoDaddy\WordPress\MWC\Common\Schedule\Types\SingleAction;
use GoDaddy\WordPress\MWC\Common\Traits\HasConditionCheckTrait;

/**
 * Schedules an action.
 */
class Schedule
{
    use HasConditionCheckTrait;

    /** @var string schedule type */
    protected $scheduleType;

    /** @var string scheduled item name */
    protected $name;

    /** @var DateTime time when the schedule should run */
    protected $scheduleAt;

    /** @var mixed[] scheduled item hook arguments, defaults to empty array */
    protected $arguments = [];

    /** @var string optional name of the collection the scheduled item belongs to */
    protected $collectionName = '';

    /** @var bool Whether the action should be unique. */
    protected bool $unique = false;

    /** @var int actions with lower values take precedence over actions with higher values */
    protected int $priority = 10;

    /**
     * Instantiates a single action as an item to schedule.
     *
     * @return SingleAction
     */
    public static function singleAction() : SingleAction
    {
        return new SingleAction();
    }

    /**
     * Instantiates a recurring action as an item to schedule.
     *
     * @return RecurringAction
     */
    public static function recurringAction() : RecurringAction
    {
        return new RecurringAction();
    }

    /**
     * Sets the schedule type for the item to schedule.
     *
     * @param string $value a schedule type
     * @return $this
     */
    protected function setType(string $value) : Schedule
    {
        $this->scheduleType = $value;

        return $this;
    }

    /**
     * Gets the schedule type of the item to schedule.
     *
     * @return string
     */
    public function getType() : string
    {
        return $this->scheduleType ?: '';
    }

    /**
     * Sets the name of the item to schedule.
     *
     * This would be the WordPress action hook name for scheduled actions.
     *
     * @param string $value
     * @return $this
     */
    public function setName(string $value) : Schedule
    {
        $this->name = $value;

        return $this;
    }

    /**
     * Sets the schedule time.
     *
     * @param DateTime $value
     * @return $this
     */
    public function setScheduleAt(DateTime $value) : Schedule
    {
        $this->scheduleAt = $value;

        return $this;
    }

    /**
     * Sets the name of the collection the scheduled item belongs to.
     *
     * @param string $value
     * @return $this
     */
    public function setCollection(string $value) : Schedule
    {
        $this->collectionName = $value;

        return $this;
    }

    /**
     * Sets the Action Scheduler's unique flag for the scheduled item.
     *
     * IMPORTANT: This flag only prevents the same action from being duplicated if the name and group match,
     * it ignores the arguments!
     *
     * Meaning it will block the same action from being scheduled again even if the arguments are different.
     *
     * @param bool $value (default: true)
     * @return $this
     */
    public function setUniqueByName(bool $value = true) : Schedule
    {
        $this->unique = $value;

        return $this;
    }

    /**
     * Sets schedule arguments.
     *
     * These arguments will be passed over every time the corresponding schedule triggers.
     *
     * @param mixed ...$value one or more arguments to unpack
     * @return $this
     */
    public function setArguments(...$value) : Schedule
    {
        $this->arguments = $value;

        return $this;
    }

    /**
     * Sets the priority for the scheduled action.
     *
     * Actions with lower values take precedence over actions with higher values.
     *
     * @return $this
     */
    public function setPriority(int $value) : Schedule
    {
        $this->priority = max(0, min($value, 250));

        return $this;
    }

    /**
     * Determines whether the item should be scheduled as defined by a scheduling condition, if present.
     *
     * @return bool
     */
    protected function shouldSchedule() : bool
    {
        return $this->conditionPasses();
    }

    /**
     * Validates the current item schedule.
     *
     * @param string $validationContext verb used internally to build more meaningful exception messages
     * @return void
     * @throws InvalidScheduleException
     */
    public function validate(string $validationContext = 'handle') : void
    {
        if (! $this->name) {
            throw new InvalidScheduleException(sprintf('Cannot %1$s a %2$s action: the name of the action to schedule is not specified.', $validationContext, $this->getType()));
        }

        if (! $this->scheduleAt instanceof DateTime) {
            throw new InvalidScheduleException(sprintf('Cannot %1$s a %2$s action: the time to schedule the action for is not specified.', $validationContext, $this->getType()));
        }
    }

    /**
     * Unschedules the current item.
     *
     * There is a subtle difference between {@see as_unschedule_all_actions()} and {@see as_unschedule_action()}.
     * From the phpdoc of {@see as_unschedule_action()}:
     *     "While only the next instance of a recurring or cron action is unscheduled by this method, that will also prevent
     *     all future instances of that recurring or cron action from being run. Recurring and cron actions are scheduled in
     *     a sequence instead of all being scheduled at once. Each successive occurrence of a recurring action is scheduled
     *     only after the former action is run. If the next instance is never run, because it's unscheduled by this function,
     *     then the following instance will never be scheduled (or exist), which is effectively the same as being unscheduled
     *     by this method also."
     *
     * @param bool $all whether to unschedule only the next item (false) or all of them (true) for the current action, if applicable.
     * @return void
     * @throws InvalidScheduleException
     */
    public function unschedule(bool $all = false) : void
    {
        $this->validate('unschedule');

        $unscheduleFunction = $all ? 'as_unschedule_all_actions' : 'as_unschedule_action';

        $unscheduleFunction(
            $this->name,
            $this->arguments,
            $this->collectionName
        );
    }

    /**
     * Determines if the current item was scheduled.
     *
     * @return bool
     */
    public function isScheduled() : bool
    {
        if (! $this->name) {
            return false;
        }

        // `as_has_scheduled_action()` is available since Action Scheduler 3.3 (WooCommerce 5.7.2 and older)
        if (function_exists('as_has_scheduled_action')) {
            return as_has_scheduled_action($this->name, $this->arguments, $this->collectionName);
        }

        // `as_next_scheduled_action()` is less performant than the function above but will work in older WooCommerce versions
        return (bool) as_next_scheduled_action($this->name, $this->arguments, $this->collectionName);
    }

    /**
     * Gets the time when the current item is scheduled for its next occurrence.
     *
     * @return DateTime|null
     */
    public function getNextScheduledTime() : ?DateTime
    {
        $timestamp = $this->name ? as_next_scheduled_action($this->name, $this->arguments, $this->collectionName) : null;

        try {
            return is_int($timestamp) ? new DateTime('@'.$timestamp) : null;
        } catch (Exception $exception) {
            return null;
        }
    }
}
