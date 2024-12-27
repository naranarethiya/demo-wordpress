<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Models\Products\Product as CommonProduct;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Core\Traits\ShouldLoadOnlyIfWooCommerceIsEnabledTrait;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\ProductAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\NewWooCommerceObjectFlag;
use WC_Product;
use WP_Post;

/**
 * A WooCommerce interceptor to hook on product actions and filters.
 */
class ProductInterceptor extends AbstractInterceptor
{
    use ShouldLoadOnlyIfWooCommerceIsEnabledTrait;

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function addHooks()
    {
        Register::action()
            ->setGroup('wp_insert_post')
            ->setHandler([$this, 'onWpInsertPost'])
            ->setArgumentsCount(3)
            ->execute();

        Register::action()
            ->setGroup('woocommerce_update_product')
            ->setHandler([$this, 'onWooCommerceUpdateProduct'])
            ->execute();

        Register::action()
            ->setGroup('trashed_post')
            ->setHandler([$this, 'onTrashedPost'])
            ->execute();

        Register::action()
            ->setGroup('before_delete_post')
            ->setHandler([$this, 'onBeforeDeletePost'])
            ->execute();
    }

    /**
     * Turns the new product flag on if the post created was a product.
     *
     * @internal
     *
     * @param int|string $postId
     * @param WP_Post $post
     * @param bool $isUpdate
     */
    public function onWpInsertPost($postId, $post, $isUpdate)
    {
        $this->maybeFlagNewProduct($postId, $post, $isUpdate);
    }

    /**
     * Calls the core product CRUD methods.
     *
     * @internal
     *
     * @param int $postId
     *
     * @throws Exception
     */
    public function onWooCommerceUpdateProduct($postId) : void
    {
        if (! ($wcProduct = ProductsRepository::get((int) $postId))) {
            return;
        }

        $newProductFlag = NewWooCommerceObjectFlag::getNewInstance($wcProduct);

        $product = $this->getConvertedProduct($wcProduct);

        if ($newProductFlag->isOn()) {
            $product->save();

            $newProductFlag->turnOff();
        } else {
            $product->update();
        }
    }

    /**
     * Handles product trashed.
     *
     * @internal
     *
     * @param int|string $postId
     *
     * @throws Exception
     */
    public function onTrashedPost($postId) : void
    {
        $this->handleTrashedOrDeletedProduct((int) $postId);
    }

    /**
     * Handles product permanent/force delete.
     *
     * @internal
     *
     * @param int|string $postId
     *
     * @throws Exception
     */
    public function onBeforeDeletePost($postId) : void
    {
        $this->handleTrashedOrDeletedProduct((int) $postId);
    }

    /**
     * Calls the core product delete method.
     *
     * @param int $postId
     * @return void
     * @throws Exception
     */
    protected function handleTrashedOrDeletedProduct(int $postId) : void
    {
        if (! ($wcProduct = ProductsRepository::get($postId))) {
            return;
        }

        $this->getConvertedProduct($wcProduct)->delete();
    }

    /**
     * Turns the new product flag on if the post created was a product.
     *
     * @param int|string $postId
     * @param WP_Post $post
     * @param bool $isUpdate
     */
    protected function maybeFlagNewProduct($postId, $post, $isUpdate) : void
    {
        if (! $isUpdate && $post->post_type === 'product' && $flag = $this->maybeGetNewProductFlag((int) $postId)) {
            $flag->turnOn();
        }
    }

    /**
     * Gets the new product flag instance for the given coupon id.
     *
     * @param int $productId
     * @return NewWooCommerceObjectFlag|null
     */
    protected function maybeGetNewProductFlag(int $productId) : ?NewWooCommerceObjectFlag
    {
        if ($product = ProductsRepository::get($productId)) {
            return NewWooCommerceObjectFlag::getNewInstance($product);
        }

        return null;
    }

    /**
     * Converts a WooCommerce product object into a native product object.
     *
     * @param WC_Product $product
     * @return CommonProduct
     * @throws Exception
     */
    protected function getConvertedProduct(WC_Product $product) : CommonProduct
    {
        return (new ProductAdapter($product))->convertFromSource();
    }
}
