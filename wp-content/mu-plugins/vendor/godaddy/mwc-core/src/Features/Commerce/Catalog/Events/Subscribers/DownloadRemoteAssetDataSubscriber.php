<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\Subscribers;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Schedule\Schedule;
use GoDaddy\WordPress\MWC\Common\Schedule\Types\SingleAction;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\AttachmentsInsertedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\RemoteAssetDownloadInterceptor;

/**
 * Listens to {@see AttachmentsInsertedEvent}, to respond by dispatching a {@see RemoteAssetDownloadInterceptor}
 * async job for each inserted asset.
 */
class DownloadRemoteAssetDataSubscriber implements SubscriberContract
{
    /**
     * Handles the event.
     *
     * @param EventContract $event
     * @return void
     */
    public function handle(EventContract $event) : void
    {
        if (! $event instanceof AttachmentsInsertedEvent) {
            return;
        }

        foreach ($event->attachmentIds as $attachmentId) {
            $this->maybeScheduleJob($attachmentId);
        }
    }

    /**
     * Schedules an async job to download the remote asset, if it's not already scheduled.
     *
     * @param int $attachmentId
     * @return void
     */
    protected function maybeScheduleJob(int $attachmentId) : void
    {
        $job = $this->getJob($attachmentId);

        if (! $job->isScheduled()) {
            try {
                $job->schedule();
            } catch(Exception $e) {
            }
        }
    }

    /**
     * Gets the job data.
     *
     * @param int $attachmentId
     * @return SingleAction
     */
    protected function getJob(int $attachmentId) : SingleAction
    {
        return Schedule::singleAction()
            ->setName(RemoteAssetDownloadInterceptor::JOB_NAME)
            ->setArguments($attachmentId)
            ->setScheduleAt(new DateTime());
    }
}
