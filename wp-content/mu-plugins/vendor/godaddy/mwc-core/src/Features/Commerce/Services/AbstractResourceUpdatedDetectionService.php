<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services;

use DateTime;
use DateTimeZone;
use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Events\AbstractRemoteResourceUpdatedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractResourceAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\AbstractResourceUpdatesRepository;

/**
 * Abstract resource updated event broadcast service.
 *
 * This service is responsible to broadcast a {@see AbstractRemoteResourceUpdatedEvent} when it detects that a resource has been updated remotely.
 * Child implementations must define the concrete event class and resource updates repository instance to use.
 * Methods to get the remote resource ID and the current updated at date can be overridden accordingly.
 */
abstract class AbstractResourceUpdatedDetectionService
{
    /** @var class-string<AbstractRemoteResourceUpdatedEvent> child classes can define the concrete event class to use for the event to broadcast */
    protected string $resourceUpdatedEventClass;

    /** @var AbstractResourceUpdatesRepository */
    protected AbstractResourceUpdatesRepository $resourceUpdatesRepository;

    /**
     * Determines whether the remote resource has been updated since our last saved `updatedAt` value.
     *
     * @param DateTime $remoteUpdatedAt
     * @param DateTime|null $lastKnownUpdatedAt
     * @return bool
     * @phpstan-assert-if-true !null $lastKnownUpdatedAt
     */
    protected function hasRemoteResourceBeenUpdated(DateTime $remoteUpdatedAt, ?DateTime $lastKnownUpdatedAt) : bool
    {
        return empty($lastKnownUpdatedAt) || $lastKnownUpdatedAt->getTimestamp() < $remoteUpdatedAt->getTimestamp();
    }

    /**
     * Checks if any of the supplied resources have been updated remotely.
     *
     * @param AbstractResourceAssociation|AbstractResourceAssociation[] $resourceAssociations one or more resource associations of the same type
     * @return void
     */
    public function detectUpdatesForResources($resourceAssociations) : void
    {
        if (! $this->shouldDetectUpdates()) {
            return;
        }

        /** @var AbstractResourceAssociation[] $resourceAssociations */
        $resourceAssociations = ArrayHelper::wrap($resourceAssociations);

        foreach ($resourceAssociations as $resourceAssociation) {
            $remoteId = $this->getRemoteResourceId($resourceAssociation->remoteResource);
            $remoteUpdatedAt = $this->getRemoteUpdatedAt($resourceAssociation->remoteResource);

            if ($remoteId && $remoteUpdatedAt) {
                $this->maybeBroadcastResourceUpdatedEvent($resourceAssociation, $remoteId, $remoteUpdatedAt);
            }
        }
    }

    /**
     * Determines whether we should check for updates.
     *
     * This defaults to `true` (always check) but child implementations can override this as required.
     *
     * @return bool
     */
    protected function shouldDetectUpdates() : bool
    {
        return true;
    }

    /**
     * Maybe broadcasts a {@see AbstractRemoteResourceUpdatedEvent} if the resource last updated at date is newer than the last known locally.
     *
     * @param AbstractResourceAssociation $resourceAssociation
     * @param string $remoteId
     * @param DateTime $remoteUpdatedAt
     * @return void
     */
    protected function maybeBroadcastResourceUpdatedEvent(AbstractResourceAssociation $resourceAssociation, string $remoteId, DateTime $remoteUpdatedAt) : void
    {
        $lastKnownUpdatedAt = $this->getLastKnownUpdatedAt($remoteId);

        if ($this->hasRemoteResourceBeenUpdated($remoteUpdatedAt, $lastKnownUpdatedAt)) {
            try {
                $this->updateLocalUpdatedAtValue($remoteId, $remoteUpdatedAt);

                Events::broadcast($this->getResourceUpdatedEventInstance($resourceAssociation, $lastKnownUpdatedAt));
            } catch(Exception $e) {
                SentryException::getNewInstance('Failed to handle remote resource update.', $e);
            }
        }
    }

    /**
     * Gets the remote resource ID.
     *
     * @param object $resource
     * @return string|null
     */
    protected function getRemoteResourceId(object $resource) : ?string
    {
        return $resource->id ?? null;
    }

    /**
     * Gets the remote resource's current updatedAt value. This is the source of truth for when the resource was actually updated.
     *
     * @param object $resource
     * @return DateTime|null
     */
    protected function getRemoteUpdatedAt(object $resource) : ?DateTime
    {
        if (empty($resource->updatedAt)) {
            return null;
        }

        try {
            return new DateTime($resource->updatedAt, new DateTimeZone('UTC'));
        } catch(Exception $e) {
            SentryException::getNewInstance(sprintf('Failed to convert remote updatedAt value "%s" to DateTimeInstance.', $resource->updatedAt), $e);

            return null;
        }
    }

    /**
     * Gets the last known updatedAt value for a resource. This is saved in the local database.
     *
     * @param string $remoteId
     * @return DateTime|null
     */
    protected function getLastKnownUpdatedAt(string $remoteId) : ?DateTime
    {
        if (! $lastUpdatedAtString = $this->resourceUpdatesRepository->getUpdatedAt($remoteId)) {
            return null;
        }

        try {
            return new DateTime($lastUpdatedAtString, new DateTimeZone('UTC'));
        } catch(Exception $e) {
            SentryException::getNewInstance(sprintf('Failed to convert local updatedAt value "%s" to DateTimeInstance.', $lastUpdatedAtString), $e);

            return null;
        }
    }

    /**
     * Persists the new `updatedAt` value to the local database.
     *
     * @param string $remoteId
     * @param DateTime $remoteUpdatedAt
     * @return void
     * @throws WordPressDatabaseException
     */
    protected function updateLocalUpdatedAtValue(string $remoteId, DateTime $remoteUpdatedAt) : void
    {
        $this->resourceUpdatesRepository->addOrUpdateUpdatedAt($remoteId, $remoteUpdatedAt->format('Y-m-d H:i:s'));
    }

    /**
     * Gets an instance of the resource updated event.
     *
     * @param AbstractResourceAssociation $resource
     * @param ?DateTime $lastKnownUpdatedAt
     * @return AbstractRemoteResourceUpdatedEvent
     */
    protected function getResourceUpdatedEventInstance(AbstractResourceAssociation $resource, ?DateTime $lastKnownUpdatedAt) : AbstractRemoteResourceUpdatedEvent
    {
        return new $this->resourceUpdatedEventClass($resource, $lastKnownUpdatedAt);
    }
}
