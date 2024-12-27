<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataSources\WooCommerce\Builders;

use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\ProductsInsertedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\InsertLocalProductService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\PoyntProductAssociationService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\DataSources\WooCommerce\Builders\AbstractResourceAssociationBuilder;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\ResourceMapCollection;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractResourceAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\ProductMapRepository;

/**
 * Builds a {@see ProductAssociation} between a local product ID and its remote object {@see ProductBase} equivalent.
 */
class ProductAssociationBuilder extends AbstractResourceAssociationBuilder
{
    /** @var PoyntProductAssociationService service to aid in associating remote products that were written by Poynt with their local equivalents */
    protected PoyntProductAssociationService $poyntProductAssociationService;

    /** @var string name of the {@see ProductBase} property that contains the remote Commerce UUID */
    protected string $remoteObjectIdProperty = 'productId';

    /**
     * Constructor.
     *
     * @param ProductMapRepository $resourceMapRepository
     * @param InsertLocalProductService $insertLocalResourceService
     * @param PoyntProductAssociationService $poyntProductAssociationService
     */
    public function __construct(ProductMapRepository $resourceMapRepository, InsertLocalProductService $insertLocalResourceService, PoyntProductAssociationService $poyntProductAssociationService)
    {
        $this->poyntProductAssociationService = $poyntProductAssociationService;

        parent::__construct($resourceMapRepository, $insertLocalResourceService);
    }

    /**
     * Instantiates a new {@see ProductAssociation} using the supplied data, which contains a remote resource and local ID.
     *
     * @param array{
     *     remoteResource: ProductBase,
     *     localId: int,
     * } $data
     * @return ProductAssociation
     */
    protected function makeResourceAssociation(array $data) : AbstractResourceAssociation
    {
        return ProductAssociation::getNewInstance($data);
    }

    /**
     * Gets the local ID of the provided remote resource. If no local ID exists, return null.
     *
     * This method is overridden from {@see AbstractResourceAssociationBuilder::getRemoteResourceLocalId()} so that we
     * can add the extra Poynt check.
     *
     * @param ProductBase $resource
     * @param ResourceMapCollection $resourceMapCollection
     *
     * @return ?int
     * @throws WordPressDatabaseException
     */
    protected function getRemoteResourceLocalId(AbstractDataObject $resource, ResourceMapCollection $resourceMapCollection) : ?int
    {
        // find the database row the corresponds to the provided `$resource` object
        if ($localId = $this->getRemoteResourceLocalIdFromMappedIds($resource, $resourceMapCollection)) {
            return $localId;
        }

        // if no match from the mapping table, check if this is a Poynt product
        $poyntProduct = $this->poyntProductAssociationService->getLocalPoyntProductForRemoteResource($resource);
        if ($poyntProduct && $poyntProduct->getId() && $resource->productId) {
            $this->resourceMapRepository->add($poyntProduct->getId(), $resource->productId);

            return $poyntProduct->getId();
        }

        return null;
    }

    /**
     * Determines whether the provided remote resource should be inserted into the local database.
     * We do not want to insert products that have been deleted remotely or products that are inactive remotely.
     *
     * @param ProductBase $remoteResource
     * @return bool
     */
    protected function shouldInsertLocalResource(AbstractDataObject $remoteResource) : bool
    {
        return empty($remoteResource->deletedAt) && $remoteResource->active;
    }

    /**
     * {@inheritDoc}
     */
    protected function maybeBroadcastInsertedResourcesEvent(array $associations) : void
    {
        $this->maybeBroadcastEvent(CatalogIntegration::class, ProductsInsertedEvent::getNewInstance($associations));
    }
}
