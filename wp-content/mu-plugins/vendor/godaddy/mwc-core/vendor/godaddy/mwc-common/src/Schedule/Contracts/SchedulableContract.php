<?php

namespace GoDaddy\WordPress\MWC\Common\Schedule\Contracts;

use GoDaddy\WordPress\MWC\Common\Schedule\Exceptions\InvalidScheduleException;

/**
 * A contract for items that can be scheduled.
 */
interface SchedulableContract
{
    /**
     * Constructor.
     */
    public function __construct();

    /**
     * Validates the schedule of an item.
     *
     * @return void
     * @throws InvalidScheduleException
     */
    public function validate() : void;

    /**
     * Schedules the item.
     *
     * @return void
     */
    public function schedule() : void;

    /**
     * Unschedules the item.
     *
     * @return void
     */
    public function unschedule() : void;
}
