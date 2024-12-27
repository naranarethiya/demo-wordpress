<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use WP_Query;

/**
 * A trait for classes that need to determine if the current {@see WP_Query} is for a product post type.
 */
trait CanDetermineWpQueryProductPostTypeTrait
{
    /**
     * Determines whether the query is for a "product" or "product_variation" post type.
     *
     * @param WP_Query $wpQuery
     * @return bool
     */
    protected function isProductQuery(WP_Query $wpQuery) : bool
    {
        if (empty($wpQuery->query_vars['post_type'] ?? null)) {
            return false;
        }

        $productPostTypes = [
            CatalogIntegration::PRODUCT_POST_TYPE,
            CatalogIntegration::PRODUCT_VARIATION_POST_TYPE,
        ];

        return count(array_intersect($productPostTypes, ArrayHelper::wrap($wpQuery->query_vars['post_type']))) > 0;
    }
}
