<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsMappingStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\ProductMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractMappingStrategyFactory;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

class ProductsMappingStrategyFactory extends AbstractMappingStrategyFactory
{
    protected ProductMapRepository $productMapRepository;

    public function __construct(CommerceContextContract $commerceContext, ProductMapRepository $productMapRepository)
    {
        parent::__construct($commerceContext);

        $this->productMapRepository = $productMapRepository;
    }

    /**
     * Gets the main mapping strategy for Products.
     *
     * @param Product $model
     * @return ProductsMappingStrategyContract|null
     */
    public function getPrimaryMappingStrategyFor(object $model) : ?ProductsMappingStrategyContract
    {
        if ($model instanceof Product && $model->getId()) {
            return $this->getProductMappingStrategy();
        }

        return null;
    }

    /**
     * Get the product mapping strategy.
     *
     * @return ProductsMappingStrategyContract
     */
    protected function getProductMappingStrategy() : ProductsMappingStrategyContract
    {
        return new ProductMappingStrategy($this->productMapRepository);
    }

    /**
     * Get the fallback mapping strategy.
     *
     * @return ProductsMappingStrategyContract
     */
    public function getSecondaryMappingStrategy() : ProductsMappingStrategyContract
    {
        // TODO: Implement getSecondaryMappingStrategy() method.
        return new class implements ProductsMappingStrategyContract {
            /**
             * {@inheritDoc}
             */
            public function saveRemoteId(object $model, string $remoteId) : void
            {
                // TODO: Implement saveRemoteId() method.
            }

            /**
             * {@inheritDoc}
             */
            public function getRemoteId(object $model) : ?string
            {
                // TODO: Implement getRemoteId() method.
                return null;
            }
        };
    }
}
