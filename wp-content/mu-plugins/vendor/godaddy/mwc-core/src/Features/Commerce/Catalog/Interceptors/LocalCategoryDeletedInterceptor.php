<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers\LocalCategoryDeletedHandler;

/**
 * Intercepts when a category gets deleted locally.
 */
class LocalCategoryDeletedInterceptor extends AbstractInterceptor
{
    /**
     * Adds a hook for when a product category is deleted locally.
     * @see wp_delete_term()
     * @throws Exception
     */
    public function addHooks() : void
    {
        $taxonomy = CatalogIntegration::PRODUCT_CATEGORY_TAXONOMY;

        Register::action()
            ->setGroup("delete_{$taxonomy}")
            ->setHandler([LocalCategoryDeletedHandler::class, 'handle'])
            ->setArgumentsCount(4)
            ->execute();
    }
}
