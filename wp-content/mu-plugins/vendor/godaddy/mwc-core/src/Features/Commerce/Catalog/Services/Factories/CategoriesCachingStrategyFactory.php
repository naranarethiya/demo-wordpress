<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Factories;

use Exception;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Factories\CachingStrategyFactory;

/**
 * Factory to build a caching strategy for product categories.
 *
 * We want to use persistent caching on all pages, except the admin "Edit Product Category" page.
 */
class CategoriesCachingStrategyFactory extends CachingStrategyFactory
{
    /**
     * {@inheritDoc}
     */
    protected function canUsePersistentCachingStrategy() : bool
    {
        return ! $this->isEditProductCategoryPage();
    }

    /**
     * Determines whether we are on the "Edit Product Category" admin page.
     *
     * @return bool
     */
    protected function isEditProductCategoryPage() : bool
    {
        try {
            $taxonomy = CatalogIntegration::PRODUCT_CATEGORY_TAXONOMY;

            return WordPressRepository::isAdmin() && WordPressRepository::isCurrentScreen("edit-{$taxonomy}");
        } catch(Exception $e) {
            return false;
        }
    }
}
