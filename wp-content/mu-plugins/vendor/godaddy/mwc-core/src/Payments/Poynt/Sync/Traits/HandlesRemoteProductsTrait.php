<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Sync\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\ProductDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\InvalidProductException;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * A trait for classes that handle remote Poynt products.
 */
trait HandlesRemoteProductsTrait
{
    /**
     * Handles a remote product.
     *
     * @param Product $product
     * @return Product
     * @throws InvalidProductException|Exception
     */
    protected function handleRemoteProduct(Product $product) : Product
    {
        if ($existingProduct = $this->getProductDataStore()->readFromRemoteId($product->getRemoteId())) {
            return $this->handleExistingProduct($existingProduct, $product);
        }

        // alert of the product SKU already exists in WooCommerce.
        if ($existingProductId = wc_get_product_id_by_sku($product->getSku())) {
            throw new InvalidProductException(__("Product {$existingProductId} with SKU {$product->getSku()} already exists in WooCommerce", 'mwc-core'));
        }

        return $this->handleNewProduct($product);
    }

    /**
     * Handles an existing product.
     *
     * @param Product $existingProduct
     * @param Product $remoteProduct
     * @return Product
     * @throws Exception
     */
    protected function handleExistingProduct(Product $existingProduct, Product $remoteProduct) : Product
    {
        $remoteProduct->setId($existingProduct->getId());

        return $this->handleProductSave($remoteProduct);
    }

    /**
     * Handles a new product from Poynt.
     *
     * @param Product $product
     * @return Product
     * @throws Exception
     */
    protected function handleNewProduct(Product $product) : Product
    {
        $product->setSource('poynt');

        return $this->handleProductSave($product);
    }

    /**
     * Handles saving a product.
     *
     * @param Product $product
     * @return Product
     * @throws Exception
     */
    protected function handleProductSave(Product $product) : Product
    {
        return $this->getProductDataStore()->save($product);
    }

    /**
     * Gets the product data store.
     *
     * @return ProductDataStore
     */
    protected function getProductDataStore() : ProductDataStore
    {
        return new ProductDataStore('poynt');
    }
}
