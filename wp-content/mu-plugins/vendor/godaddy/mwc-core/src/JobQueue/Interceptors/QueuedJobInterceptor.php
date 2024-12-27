<?php

namespace GoDaddy\WordPress\MWC\Core\JobQueue\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\JobQueue\Helpers\JobConfigHelper;
use GoDaddy\WordPress\MWC\Core\JobQueue\Services\ScheduledJobQueueDispatchService;

/**
 * Action scheduler interceptor for queued jobs.
 */
class QueuedJobInterceptor extends AbstractInterceptor
{
    /**
     * Adds hooks.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup(ScheduledJobQueueDispatchService::ACTION_SCHEDULER_JOB_NAME)
            ->setArgumentsCount(3)
            ->setHandler([$this, 'handleJob'])
            ->execute();
    }

    /**
     * Handles a queued job.
     *
     * @param string $jobKey
     * @param string[] $chainKeys
     * @param ?array<mixed> $args
     * @return void
     */
    public function handleJob(string $jobKey, array $chainKeys, array $args = null) : void
    {
        try {
            $job = JobConfigHelper::getJobByKey($jobKey);

            $job
                ->setChain(JobConfigHelper::convertJobKeysToClassNames($chainKeys))
                ->setArgs($args)
                ->handle();
        } catch(Exception $e) {
            SentryException::getNewInstance("Failed to handle queued job {$jobKey}", $e);
        }
    }
}
