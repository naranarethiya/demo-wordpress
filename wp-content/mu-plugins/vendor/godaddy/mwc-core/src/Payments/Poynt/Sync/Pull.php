<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Sync;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Sync\Jobs\SyncJob;

/**
 * The Poynt products pull sync handler.
 *
 * Holds configuration details about the pull settings, and allows initiating a product sync from Poynt to WooCommerce.
 */
class Pull extends AbstractSyncHandler
{
    /** @var string name */
    protected static $name = 'pull';

    /**
     * Starts the pull sync handler.
     *
     * @return SyncJob|null
     * @throws Exception
     */
    public static function start()
    {
        if (static::isSyncing()) {
            return null;
        }

        $job = SyncJob::create([
            'owner'      => 'poynt',
            'objectType' => 'product',
        ]);

        as_schedule_single_action(
            (new DateTime('now'))->getTimestamp(),
            'mwc_pull_poynt_objects',
            [
                'jobId' => $job->getId(),
            ]
        );

        static::setIsSyncing(true);

        return $job;
    }
}
