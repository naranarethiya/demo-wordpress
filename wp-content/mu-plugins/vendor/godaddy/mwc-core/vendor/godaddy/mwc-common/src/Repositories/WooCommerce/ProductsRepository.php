<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\Traits\HasWooCommerceDataAccessorsTrait;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use WC_Product;
use WC_Product_External;
use WC_Product_Grouped;
use WC_Product_Variable;
use WC_Product_Variation;

/**
 * Repository for handling WooCommerce products.
 *
 * @property WC_Product $object
 */
class ProductsRepository
{
    use HasWooCommerceDataAccessorsTrait;

    /**
     * Initializes a new WooCommerce product to be built.
     *
     * @param WC_Product|WC_Product_External|WC_Product_Grouped|WC_Product_Variable|WC_Product_Variation $object
     */
    public function __construct(WC_Product $object)
    {
        $this->object = $object;
    }

    /**
     * Gets a WooCommerce product object.
     *
     * @param int $id product ID
     * @return WC_Product|WC_Product_External|WC_Product_Grouped|WC_Product_Variable|WC_Product_Variation|null
     */
    public static function get(int $id) : ?WC_Product
    {
        return wc_get_product($id) ?: null;
    }

    /**
     * Gets the current product from context.
     *
     * @return WC_Product|null
     */
    public static function getCurrent() : ?WC_Product
    {
        $productId = static::getCurrentId();

        return $productId ? static::get($productId) : null;
    }

    /**
     * Gets the ID of the current product from context.
     *
     * @return int|null
     */
    public static function getCurrentId() : ?int
    {
        if (! WooCommerceRepository::isProductPage()) {
            return null;
        }

        $productId = get_the_ID();

        return is_numeric($productId) ? (int) $productId : null;
    }

    /**
     * Gets an array of WooCommerce product objects.
     *
     * @link https://github.com/woocommerce/woocommerce/wiki/wc_get_products-and-WC_Product_Query for accepted args and extended usage
     *
     * @param array<string, mixed> $args
     * @return WC_Product[]
     */
    public static function query(array $args) : array
    {
        if (! WooCommerceRepository::isWooCommerceActive()) {
            return [];
        }

        return ArrayHelper::wrap(wc_get_products($args));
    }

    /**
     * Starts a new instance seeding a WooCommerce product of a given type.
     *
     * @param string $productType class name of the product
     * @param array $properties optional properties to set as key-values
     * @param array $metaData optional metadata to set as key-values
     * @return ProductsRepository
     * @throws Exception
     */
    public static function seed(string $productType = WC_Product::class, array $properties = [], array $metaData = []) : ProductsRepository
    {
        if (! class_exists($productType)) {
            throw new Exception("Product of class {$productType} does not exist.");
        }

        /* @var WC_Product $product */
        $product = new $productType();

        if (! $product instanceof WC_Product) {
            throw new Exception("{$productType} is not a valid product.");
        }

        return static::for($product)->setData($properties, $metaData);
    }
}
