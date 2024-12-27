<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Events;

use DateTime;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractResourceAssociation;

/**
 * Base event model for resources that may be updated remotely.
 *
 * This event should be broadcast when a resource is updated remotely.
 *
 * @method static static getNewInstance(AbstractResourceAssociation $resource, ?DateTime $lastKnownUpdatedAt)
 */
abstract class AbstractRemoteResourceUpdatedEvent implements EventContract
{
    use CanGetNewInstanceTrait;

    /** @var AbstractResourceAssociation resource object */
    protected AbstractResourceAssociation $resource;

    /** @var DateTime|null datetime for the last time the resource was updated */
    protected ?DateTime $lastUpdatedAt;

    /**
     * Constructor.
     *
     * @param AbstractResourceAssociation $resource association including the resource object that has been updated remotely
     * @param DateTime|null $lastKnownUpdatedAt last known updatedAt value (prior to the latest change), as saved in the local database
     */
    public function __construct(AbstractResourceAssociation $resource, ?DateTime $lastKnownUpdatedAt)
    {
        $this->resource = $resource;
        $this->lastUpdatedAt = $lastKnownUpdatedAt;
    }

    /**
     * Gets the resource that has been updated remotely.
     *
     * @return AbstractResourceAssociation
     */
    public function getResource() : AbstractResourceAssociation
    {
        return $this->resource;
    }

    /**
     * Gets the datetime when the resource was last updated.
     *
     * @return DateTime|null datetime string
     */
    public function getLastUpdatedAt() : ?DateTime
    {
        return $this->lastUpdatedAt;
    }
}
