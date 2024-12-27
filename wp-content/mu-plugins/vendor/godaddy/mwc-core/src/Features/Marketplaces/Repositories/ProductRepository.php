<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Repositories;

use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use WC_Product;

class ProductRepository
{
    /**
     * Checks if a product meets the requirements to be listed in Marketplaces:
     * - Simple or variable product (not virtual or downloadable)
     * - Published product
     * - SKU must be defined
     * - Manage stock enabled and stock quantity > 0
     * - No backorders allowed
     * - Brand and Condition fields filled.
     *
     * @param int $productId
     * @return bool
     */
    public static function canProductBeListed(int $productId) : bool
    {
        /* @var WC_Product $wcProduct */
        $wcProduct = ProductsRepository::get($productId);

        return ! (empty($wcProduct)
            || $wcProduct->is_virtual() || $wcProduct->is_downloadable()
            || 'publish' !== $wcProduct->get_status()
            || empty($wcProduct->get_sku())
            || ! $wcProduct->managing_stock() || empty($wcProduct->get_stock_quantity())
            || $wcProduct->backorders_allowed()
        );
    }
}
