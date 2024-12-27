<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\Subscribers;

use DateTime;
use DateTimeZone;
use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\ProductCreatedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\ProductUpdatedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\RemoteProductUpdatesRepository;

/**
 * Listens to {@see ProductCreatedEvent} and {@see ProductUpdatedEvent} to update the last known `updatedAt` timestamp
 * in the local database after we've written product data to the remote platform.
 *
 * More detailed order of operations is as follows:
 *
 * - Save product in the local database.
 * - We write that change to the remote platform.
 * - We parse the product data out of the API response (including the `updatedAt` value).
 * - We fire an event {@see ProductCreatedEvent} or {@see ProductUpdatedEvent}.
 * - This subscriber kicks in to update the local database with that `updatedAt` value from the API response.
 */
class UpdateProductLastKnownUpdatedAtTimeSubscriber implements SubscriberContract
{
    protected RemoteProductUpdatesRepository $remoteProductUpdatesRepository;

    public function __construct(RemoteProductUpdatesRepository $remoteProductUpdatesRepository)
    {
        $this->remoteProductUpdatesRepository = $remoteProductUpdatesRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(EventContract $event) : void
    {
        if (! $event instanceof ProductCreatedEvent && ! $event instanceof ProductUpdatedEvent) {
            return;
        }

        try {
            $this->updateValueInDatabase(
                $this->getRemoteProductUuid($event),
                $this->getRemoteProductLastUpdatedAtTime($event)
            );
        } catch(Exception $e) {
            SentryException::getNewInstance('Failed to update product\'s last known updatedAt value.', $e);
        }
    }

    /**
     * Gets the remote product's `updatedAt` datetime value from the event.
     *
     * @param ProductCreatedEvent|ProductUpdatedEvent $event
     * @return DateTime
     * @throws Exception
     */
    protected function getRemoteProductLastUpdatedAtTime(EventContract $event) : DateTime
    {
        $dateTimeString = null;

        if ($event instanceof ProductCreatedEvent) {
            $dateTimeString = $event->remoteProduct->updatedAt;
        } elseif ($event instanceof ProductUpdatedEvent && isset($event->productAssociations[0])) {
            $dateTimeString = $event->productAssociations[0]->remoteResource->updatedAt;
        }

        if (empty($dateTimeString)) {
            throw new Exception('No updatedAt value found for remote product.');
        }

        return new DateTime($dateTimeString, new DateTimeZone('UTC'));
    }

    /**
     * Gets the remote product's UUID value from the event.
     *
     * @param ProductCreatedEvent|ProductUpdatedEvent $event
     * @return string
     * @throws Exception
     */
    protected function getRemoteProductUuid(EventContract $event) : string
    {
        $remoteProductUuid = null;

        if ($event instanceof ProductCreatedEvent) {
            $remoteProductUuid = $event->remoteProductId;
        } elseif ($event instanceof ProductUpdatedEvent && isset($event->productAssociations[0])) {
            $remoteProductUuid = $event->productAssociations[0]->remoteResource->productId;
        }

        if (empty($remoteProductUuid)) {
            throw new Exception('Remote product UUID not found in event.');
        }

        return $remoteProductUuid;
    }

    /**
     * Updates the product's last known `updatedAt` value in the local database.
     *
     * @param string $remoteId
     * @param DateTime $lastUpdatedAt
     * @return void
     * @throws WordPressDatabaseException
     */
    protected function updateValueInDatabase(string $remoteId, DateTime $lastUpdatedAt) : void
    {
        $this->remoteProductUpdatesRepository->addOrUpdateUpdatedAt($remoteId, $lastUpdatedAt->format('Y-m-d H:i:s'));
    }
}
