<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\DataStores;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\ProductAdapter;

/**
 * Data store helper for products with listings in Marketplaces.
 */
class ProductListingsDataStore
{
    /**
     * Gets all products IDs with listings in the Marketplaces.
     *
     * @param int[]|string[]|null $productIds if provided, the query will be limited to the product IDs in the array
     * @return int[]
     */
    public static function listProductsWithListings(?array $productIds) : array
    {
        $postMetaTable = DatabaseRepository::getTablePrefix().'postmeta';
        $query = "SELECT post_id FROM {$postMetaTable} WHERE meta_key = %s";
        $args = [ProductAdapter::MARKETPLACES_LISTINGS_META_KEY];

        if (! empty($productIds)) {
            $placeholders = implode(',', array_map(static function () {
                return '%d';
            }, $productIds));

            $query .= ' AND post_id IN ('.$placeholders.')';
            $args = array_merge($args, $productIds);
        }

        $results = DatabaseRepository::getResults($query, $args);

        return array_filter(array_map(static function ($post) {
            return (int) ArrayHelper::get((array) $post, 'post_id', 0);
        }, $results));
    }
}
