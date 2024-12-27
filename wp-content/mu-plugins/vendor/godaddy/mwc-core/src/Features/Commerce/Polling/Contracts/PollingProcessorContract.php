<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Polling\Contracts;

use DateInterval;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;

/**
 * Contract for polling processors.
 */
interface PollingProcessorContract extends ConditionalComponentContract
{
    /**
     * Determines if a polling job has been scheduled.
     *
     * @return bool
     */
    public function isPollingJobScheduled() : bool;

    /**
     * Determines if a polling job is in progress.
     *
     * @return bool
     */
    public function isPollingJobInProgress() : bool;

    /**
     * Schedules a polling job.
     *
     * @return void
     */
    public static function schedule() : void;

    /**
     * Gets the job interval.
     *
     * @return DateInterval
     */
    public function getJobInterval() : DateInterval;

    /**
     * Sets the job interval.
     *
     * @param DateInterval $value
     * @return $this
     */
    public function setJobInterval(DateInterval $value) : PollingProcessorContract;

    /**
     * Gets the timestamp when the last poll was performed.
     *
     * @return int|null
     */
    public function getLastPolledAt() : ?int;

    /**
     * Sets the timestamp when the last poll was performed.
     *
     * @param int $value
     * @return void
     */
    public function setLastPolledAt(int $value) : void;

    /**
     * Polling function.
     *
     * @return void
     */
    public function poll() : void;
}
