<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use WP_Term;

/**
 * Helper to determine if a category is eligible to be written to the platform.
 */
class CategoryEligibilityHelper
{
    /**
     * Determines whether it should write a {@see WP_Term} category to the platform.
     *
     * @param WP_Term $category
     * @return bool
     */
    public static function shouldWriteCategoryToPlatform(WP_Term $category) : bool
    {
        if (! CatalogIntegration::hasCommerceCapability(Commerce::CAPABILITY_WRITE)) {
            return false;
        }

        // slugs are required & we don't want to write the "Uncategorized" category
        if (empty($category->slug) || $category->slug === CatalogIntegration::INELIGIBLE_PRODUCT_CATEGORY_NAME) {
            return false;
        }

        return true;
    }
}
