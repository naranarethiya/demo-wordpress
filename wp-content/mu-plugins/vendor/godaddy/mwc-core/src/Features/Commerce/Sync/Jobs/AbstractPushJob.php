<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Sync\Jobs;

use GoDaddy\WordPress\MWC\Common\Sync\Jobs\PushSyncJob;

/**
 * A sync job for pushing objects on a schedule.
 */
abstract class AbstractPushJob extends PushSyncJob
{
    /** @var int Batch size */
    protected $batchSize = 1;

    /** @var string Push job owner */
    protected $owner = 'commerce';

    /** @var string Scheduled action hook identifier */
    protected $hookPlaceholder = 'mwc_push_%s_%s_objects';

    /** @var int The number of times the job has been attempted and failed */
    protected int $attempts = 0;

    /**
     * Returns scheduled action hook.
     *
     * @return string
     */
    protected function getScheduledActionHook() : string
    {
        return sprintf($this->hookPlaceholder, $this->getOwner(), $this->getObjectType());
    }

    /**
     * Overrides parent method validation because batchSize and owner have default values in this class.
     *
     * @param array<string, mixed> $data
     * @return void
     */
    protected static function validateDataForCreate(array $data) : void
    {
        // No-op
    }
}
