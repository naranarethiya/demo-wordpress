<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services;

use GoDaddy\WordPress\MWC\Common\Models\Term;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\CategoriesMappingStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\CategoryMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractMappingStrategyFactory;

/**
 * Factory to return a {@see CategoriesMappingStrategyContract} for the provided model.
 */
class CategoriesMappingStrategyFactory extends AbstractMappingStrategyFactory
{
    protected CategoryMapRepository $categoryMapRepository;

    public function __construct(CommerceContextContract $commerceContext, CategoryMapRepository $categoryMapRepository)
    {
        parent::__construct($commerceContext);
        $this->categoryMapRepository = $categoryMapRepository;
    }

    /**
     * Gets the main mapping strategy for product categories.
     *
     * @param object|Term $model
     * @return CategoriesMappingStrategyContract|null
     */
    public function getPrimaryMappingStrategyFor(object $model) : ?CategoriesMappingStrategyContract
    {
        return $model instanceof Term && $model->getId()
            ? $this->getCategoryMappingStrategy()
            : null;
    }

    /**
     * Gets the product category mapping strategy.
     *
     * @return CategoriesMappingStrategyContract
     */
    protected function getCategoryMappingStrategy() : CategoriesMappingStrategyContract
    {
        return new CategoriesMappingStrategy($this->categoryMapRepository);
    }

    /**
     * Gets the fallback mapping strategy.
     *
     * @return CategoriesMappingStrategyContract
     */
    public function getSecondaryMappingStrategy() : CategoriesMappingStrategyContract
    {
        // we do not have a secondary mapping strategy at this time
        return new class implements CategoriesMappingStrategyContract {
            public function saveRemoteId(object $model, string $remoteId) : void
            {
                // no-op
            }

            public function getRemoteId(object $model) : ?string
            {
                // no-op
                return null;
            }
        };
    }
}
