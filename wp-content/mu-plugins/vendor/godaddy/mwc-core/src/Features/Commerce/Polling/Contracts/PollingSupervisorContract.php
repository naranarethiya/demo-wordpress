<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Polling\Contracts;

use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
use GoDaddy\WordPress\MWC\Common\Interceptors\Contracts\InterceptorContract;

/**
 * Contract for polling supervisors.
 */
interface PollingSupervisorContract extends ConditionalComponentContract, InterceptorContract
{
    /**
     * Maybe schedules the associated polling jobs.
     *
     * @return void
     */
    public function maybeSchedulePollingJobs() : void;
}
