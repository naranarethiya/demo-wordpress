<?php

namespace GoDaddy\WordPress\MWC\Common\Sync\Jobs;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Sync\Exceptions\MissingBatchSizeException;
use GoDaddy\WordPress\MWC\Common\Sync\Exceptions\MissingOwnerException;

/**
 * A sync job for pushing objects on a schedule.
 */
class PushSyncJob extends SyncJob
{
    /** @var string placeholder used in scheduled action hook identifiers */
    protected $hookPlaceholder = 'mwc_push_%s_objects';

    /**
     * Creates and schedules a push job.
     *
     * @param array<string, mixed> $data
     * @return $this
     * @throws Exception
     */
    public static function create(array $data = []) : SyncJob
    {
        static::validateDataForCreate($data);

        return parent::create($data)->schedule();
    }

    /**
     * Schedules the push job.
     *
     * This may schedule multiple Action Scheduler actions depending on the batch size configured in the job.
     *
     * @return $this
     */
    public function schedule() : PushSyncJob
    {
        $batches = array_chunk($this->getObjectIds(), max(1, $this->getBatchSize()));

        foreach ($batches as $batchIds) {
            as_schedule_single_action(
                (new DateTime('now'))->getTimestamp(),
                $this->getScheduledActionHook(),
                [
                    'jobId'     => $this->getId(),
                    'objectIds' => $batchIds,
                ]
            );
        }

        return $this;
    }

    /**
     * Protected subroutine to return scheduled action hook.
     *
     * @return string
     */
    protected function getScheduledActionHook() : string
    {
        return sprintf($this->hookPlaceholder, $this->getOwner());
    }

    /**
     * Validates data array. Intended for use before the job is created.
     *
     * @param array<string, mixed> $data
     * @throws MissingBatchSizeException
     * @throws MissingOwnerException
     */
    protected static function validateDataForCreate(array $data) : void
    {
        if (! ArrayHelper::get($data, 'owner')) {
            throw new MissingOwnerException('The push job must have an owner.');
        }

        if (! ArrayHelper::get($data, 'batchSize')) {
            throw new MissingBatchSizeException('The push job must have a batch size.');
        }
    }
}
