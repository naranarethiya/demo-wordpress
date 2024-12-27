<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\RemoteProductUpdatedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\PatchProductCategoryIdsJobStatusHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\RemoteProductUpdatesRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractResourceUpdatedDetectionService;

/**
 * Service for broadcasting {@see RemoteProductUpdatedEvent}s.
 */
class ProductUpdatedDetectionService extends AbstractResourceUpdatedDetectionService
{
    /** @var class-string<RemoteProductUpdatedEvent> */
    protected string $resourceUpdatedEventClass = RemoteProductUpdatedEvent::class;

    /**
     * Constructor.
     *
     * @param RemoteProductUpdatesRepository $productUpdatesRepository
     */
    public function __construct(RemoteProductUpdatesRepository $productUpdatesRepository)
    {
        $this->resourceUpdatesRepository = $productUpdatesRepository;
    }

    /**
     * Gets the remote product ID.
     *
     * @param ProductBase $resource
     * @return string|null
     */
    protected function getRemoteResourceId(object $resource) : ?string
    {
        return $resource->productId;
    }

    /**
     * {@inheritDoc}
     */
    protected function shouldDetectUpdates() : bool
    {
        return CatalogIntegration::hasCommerceCapability(Commerce::CAPABILITY_DETECT_UPSTREAM_CHANGES) &&
            // In order to eliminate the chance of losing Woo data, the change detection process can only run once
            // the patch job has been completed.
            PatchProductCategoryIdsJobStatusHelper::hasRun();
    }
}
