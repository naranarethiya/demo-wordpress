<?php

namespace GoDaddy\WordPress\MWC\Core\JobQueue\Commands;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\JobQueue\Contracts\QueueableJobContract;
use GoDaddy\WordPress\MWC\Core\JobQueue\Helpers\JobConfigHelper;
use GoDaddy\WordPress\MWC\Core\JobQueue\JobQueue;
use WP_CLI;

class DispatchJobCommand
{
    /**
     * Dispatches a registered job.
     *
     * ## OPTIONS
     * <job>
     * : The key of the job to dispatch.
     *
     * [--without-overlapping]
     * : If set, the job will NOT be dispatched if it's already scheduled/in-progress.
     *
     * ## EXAMPLES
     *
     *     wp mwc dispatch patchProductCategoryAssociations
     *     wp mwc dispatch patchProductCategoryAssociations --without-overlapping
     *
     * @param array<string, mixed>|mixed $args
     * @param array<string, mixed>|mixed $assoc_args
     * @return void
     */
    public function __invoke($args, $assoc_args)
    {
        if (! class_exists('WP_CLI')) {
            return;
        }

        $jobKey = TypeHelper::string(ArrayHelper::get($args, 0), '');
        if (empty($jobKey)) {
            WP_CLI::error('Missing required job key.');

            return;
        }

        $this->dispatchJob($jobKey, TypeHelper::array($assoc_args, []));

        WP_CLI::line('Job successfully dispatched.');
    }

    /**
     * Dispatches the job with the supplied key.
     *
     * @param string $jobKey
     * @param array<string, mixed> $args command arguments
     * @return void
     */
    protected function dispatchJob(string $jobKey, array $args) : void
    {
        $jobQueue = JobQueue::getNewInstance()
            ->chain($this->makeJobChain($jobKey));

        if (ArrayHelper::get($args, 'without-overlapping')) {
            $jobQueue->withoutOverlapping();
        }

        $jobQueue->dispatch();
    }

    /**
     * Makes a valid job chain array from the supplied job key.
     *
     * @param string $jobKey
     * @return class-string<QueueableJobContract>[]
     */
    protected function makeJobChain(string $jobKey) : array
    {
        return JobConfigHelper::convertJobKeysToClassNames([$jobKey]);
    }
}
