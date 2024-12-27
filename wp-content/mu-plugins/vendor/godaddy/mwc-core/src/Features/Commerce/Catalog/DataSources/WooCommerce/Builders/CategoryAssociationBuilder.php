<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataSources\WooCommerce\Builders;

use GoDaddy\WordPress\MWC\Common\Models\Taxonomy;
use GoDaddy\WordPress\MWC\Common\Models\Term;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\Category;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\CategoryAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\InsertLocalCategoryService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\DataSources\WooCommerce\Builders\AbstractResourceAssociationBuilder;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractResourceAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\CategoryMapRepository;

/**
 * Builds a {@see CategoryAssociation} between a local category ID and its remote object {@see Category} equivalent.
 */
class CategoryAssociationBuilder extends AbstractResourceAssociationBuilder
{
    /** @var string name of the {@see Category} property that contains the remote commerce UUID */
    protected string $remoteObjectIdProperty = 'categoryId';

    public function __construct(
        CategoryMapRepository $resourceMapRepository,
        InsertLocalCategoryService $insertLocalResourceService
    ) {
        parent::__construct($resourceMapRepository, $insertLocalResourceService);
    }

    /**
     * Instantiates a new {@see CategoryAssociation} using the supplied data, which contains a remote resource and local ID.
     *
     * @param array{
     *     remoteResource: Category,
     *     localId: int,
     * } $data
     * @return CategoryAssociation
     */
    protected function makeResourceAssociation(array $data) : AbstractResourceAssociation
    {
        return CategoryAssociation::getNewInstance($data);
    }

    /**
     * Finds a local category ID based on a slug match (where remote `altId` matches the local category slug).
     *
     * @param Category $resource
     * @return int|null
     */
    protected function findUnmappedLocalResourceId(AbstractDataObject $resource) : ?int
    {
        if (! $resource->altId) {
            return null;
        }

        /** @var Term|null $term */
        $term = CatalogIntegration::withoutReads(fn () => Term::getByName($resource->altId, Taxonomy::getNewInstance()->setName(CatalogIntegration::PRODUCT_CATEGORY_TAXONOMY)));

        if (! $term || ! $term->getId()) {
            return null;
        }

        // if this term ID exists in the mapping table then we can't use it; that means it's already associated with something!
        if ($this->resourceMapRepository->getRemoteId($term->getId())) {
            return null;
        }

        return $term->getId();
    }

    /**
     * {@inheritDoc}
     *
     * @param Category $remoteResource
     */
    protected function shouldInsertLocalResource(AbstractDataObject $remoteResource) : bool
    {
        return $remoteResource->deletedAt === null;
    }
}
