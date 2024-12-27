<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events;

use DateTime;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Events\AbstractRemoteResourceUpdatedEvent;

/**
 * This event should be broadcast whenever we know a remote product has been updated in the platform.
 * This is not expected to be broadcast if we trigger our own update from WooCommerce to the platform.
 *
 * @method static static getNewInstance(ProductAssociation $product, ?DateTime $lastUpdatedAt)
 * @method ProductAssociation getResource()
 */
class RemoteProductUpdatedEvent extends AbstractRemoteResourceUpdatedEvent
{
    /**
     * Constructor.
     *
     * @param ProductAssociation $resource
     * @param DateTime|null $lastKnownUpdatedAt last known updatedAt value (prior to the latest change), as saved in the local database
     */
    public function __construct(ProductAssociation $resource, ?DateTime $lastKnownUpdatedAt)
    {
        parent::__construct($resource, $lastKnownUpdatedAt);
    }
}
