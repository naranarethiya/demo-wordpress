<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Backfill\Interceptors\Handler;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\JobQueue\Contracts\QueueableJobContract;
use GoDaddy\WordPress\MWC\Core\JobQueue\JobQueue;

/**
 * Handles the callback for the recurring backfill job.
 */
class InitiateBackfillHandler extends AbstractInterceptorHandler
{
    /**
     * Dispatches the chain of resource backfill jobs. These will be completed in the background in sequential order.
     *
     * @param ...$args
     * @return void
     */
    public function run(...$args)
    {
        try {
            if (! $jobs = $this->getRegisteredJobs()) {
                return;
            }

            JobQueue::getNewInstance()->chain($jobs)->dispatch();
        } catch(Exception $exception) {
            // catch exceptions in hook callbacks
            SentryException::getNewInstance('Failed to dispatch backfill job.', $exception);
        }
    }

    /**
     * Gets the chain of backfill jobs.
     *
     * @return class-string<QueueableJobContract>[]
     */
    protected function getRegisteredJobs() : array
    {
        $jobs = TypeHelper::array(Configuration::get('features.commerce_backfill.jobs'), []);

        return TypeHelper::arrayOfClassStrings($jobs, QueueableJobContract::class);
    }
}
