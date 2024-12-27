<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;

/**
 * Service class to aid in listing products by ID in batches.
 */
class BatchListProductsByLocalIdService extends AbstractBatchListResourcesByLocalIdService
{
    protected ProductsServiceContract $productsService;

    /** @var bool whether variants (child products) should also be queried at the same time */
    protected bool $withVariants = false;

    /**
     * Constructor.
     *
     * @param ProductsServiceContract $productsService
     */
    public function __construct(ProductsServiceContract $productsService)
    {
        $this->productsService = $productsService;
    }

    /**
     * Sets whether variants (child products) should also be queried at the same time. If set to `true`, then when
     * {@see static::batchListByLocalIds()} is called with an array of product IDs, the variants of those products will
     * be included in the request and returned.
     *
     * @param bool $value
     * @return $this
     */
    public function setWithVariants(bool $value) : BatchListProductsByLocalIdService
    {
        $this->withVariants = $value;

        return $this;
    }

    /**
     * Gets whether variants should be queried as well.
     *
     * @return bool
     */
    public function getWithVariants() : bool
    {
        return $this->withVariants;
    }

    /**
     * {@inheritDoc}
     * @return ProductAssociation[]
     */
    public function batchListByLocalIds(array $localIds) : array
    {
        if ($this->getWithVariants()) {
            try {
                /** @var int[] $localIds */
                $localIds = ArrayHelper::combine($localIds, $this->getVariantIds($localIds));
            } catch(Exception $e) {
                // use un-modified $localIds
            }
        }

        /** @var ProductAssociation[] $associations */
        $associations = parent::batchListByLocalIds($localIds);

        return $associations;
    }

    /**
     * Gets the local IDs of the variants for the supplied local parent IDs.
     *
     * @param int[] $localParentIds array of local parent product IDs
     * @return int[] array of local variant product IDs
     */
    protected function getVariantIds(array $localParentIds) : array
    {
        /** @var int[] $variantIds */
        $variantIds = CatalogIntegration::withoutReads(function () use ($localParentIds) {
            // ProductsRepository::query() isn't working here for some reason....
            return get_posts([
                'post_parent__in' => $localParentIds,
                'post_type'       => CatalogIntegration::PRODUCT_VARIATION_POST_TYPE,
                'fields'          => 'ids',
                'post_status'     => ['publish', 'private'],
                'numberposts'     => -1,
            ]);
        });

        return $variantIds;
    }

    /**
     * {@inheritDoc}
     * @return ProductAssociation[]
     */
    protected function listBatch(array $localIds) : array
    {
        return $this->productsService->listProductsByLocalIds($localIds)->getProducts();
    }
}
