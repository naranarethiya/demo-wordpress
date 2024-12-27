<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\ListCategoriesOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\CategoryAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ListCategoriesServiceContract;

/**
 * Service class to aid in listing categories by ID in batches.
 */
class BatchListCategoriesByLocalIdService extends AbstractBatchListResourcesByLocalIdService
{
    /** @var ListCategoriesServiceContract list categories service */
    protected ListCategoriesServiceContract $listCategoriesService;

    /**
     * Constructor.
     *
     * @param ListCategoriesServiceContract $listCategoriesService
     */
    public function __construct(ListCategoriesServiceContract $listCategoriesService)
    {
        $this->listCategoriesService = $listCategoriesService;
    }

    /**
     * (This is overridden just to change the return type in the docblock.).
     *
     * {@inheritDoc}
     * @return CategoryAssociation[]
     */
    public function batchListByLocalIds(array $localIds) : array
    {
        /** @var CategoryAssociation[] $associations */
        $associations = parent::batchListByLocalIds($localIds);

        return $associations;
    }

    /**
     * {@inheritDoc}
     * @return CategoryAssociation[]
     */
    protected function listBatch(array $localIds) : array
    {
        return $this->listCategoriesService->list(
            ListCategoriesOperation::getNewInstance()
                ->setLocalIds($localIds)
                ->setPageSize(count($localIds))
        );
    }
}
