<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\Subscribers;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Schedule\Schedule;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\RemoteProductUpdatedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers\SaveLocalProductAfterRemoteUpdateHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\SaveLocalProductAfterRemoteUpdateInterceptor;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Subscriber of {@see RemoteProductUpdatedEvent}.
 *
 * When a product has been updated remotely, we dispatch an async job to save that product locally.
 * {@see SaveLocalProductAfterRemoteUpdateHandler}
 */
class DispatchJobToSaveLocalProductSubscriber implements SubscriberContract
{
    /**
     * Handles the event.
     *
     * @param EventContract $event
     * @return void
     */
    public function handle(EventContract $event) : void
    {
        if (! $event instanceof RemoteProductUpdatedEvent) {
            return;
        }

        try {
            /*
             * We do the actual product saving async to prevent performance issues. Doing it during a normal page load
             * is NOT performant because of all the side effects of saving, including:
             * - Purging caches (both NGINX and WooCommerce's own transient caches);
             * - Doing queries to rebuild those caches;
             * - Rebuilding WooCommerce lookup tables;
             * etc.
             */
            $job = Schedule::singleAction()
                ->setName(SaveLocalProductAfterRemoteUpdateInterceptor::JOB_NAME)
                ->setArguments($event->getResource()->localId)
                ->setScheduleAt(new DateTime('now'));

            if (! $job->isScheduled()) {
                $job->schedule();
            }
        } catch(Exception $exception) {
            SentryException::getNewInstance($exception->getMessage(), $exception);
        }
    }
}
