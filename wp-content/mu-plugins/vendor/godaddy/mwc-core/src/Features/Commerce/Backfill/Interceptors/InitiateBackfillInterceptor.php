<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Backfill\Interceptors;

use DateInterval;
use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Schedule\Schedule;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Backfill\Interceptors\Handler\InitiateBackfillHandler;

/**
 * Interceptor to handle scheduling the recurring backfill job.
 *
 * This class is responsible for kicking off individual backfill processes. {@see InitiateBackfillHandler} for more details.
 */
class InitiateBackfillInterceptor extends AbstractInterceptor
{
    public const BACKFILL_JOB_NAME = 'mwc_gd_commerce_backfill_manager';

    /**
     * Adds hooks.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'maybeScheduleJob'])
            ->execute();

        Register::action()
            ->setGroup(static::BACKFILL_JOB_NAME)
            ->setHandler([InitiateBackfillHandler::class, 'handle'])
            ->execute();
    }

    /**
     * Schedules the recurring job if it's not already scheduled.
     *
     * @return void
     */
    public function maybeScheduleJob() : void
    {
        $job = Schedule::recurringAction()->setName(static::BACKFILL_JOB_NAME);

        if (! $job->isScheduled()) {
            try {
                $job
                    ->setScheduleAt(new DateTime('now'))
                    ->setInterval($this->getJobInterval())
                    ->schedule();
            } catch(Exception $exception) {
                // catch exceptions in hook callback to prevent runtime errors
                SentryException::getNewInstance('Failed to schedule Commerce backfill job.', $exception);
            }
        }
    }

    /**
     * Gets the job interval.
     *
     * @return DateInterval
     */
    protected function getJobInterval() : DateInterval
    {
        $intervalString = TypeHelper::string(Configuration::get('features.commerce_backfill.recurringJobDateInterval'), 'P1D');

        try {
            return new DateInterval($intervalString);
        } catch(Exception $e) {
            return new DateInterval('P1D');
        }
    }
}
