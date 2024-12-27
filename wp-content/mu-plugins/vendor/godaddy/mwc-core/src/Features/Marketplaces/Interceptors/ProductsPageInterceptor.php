<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\DataStores\ProductListingsDataStore;
use WP_Post;

/**
 * Enqueues a JavaScript file on the Product list table page.
 */
class ProductsPageInterceptor extends AbstractInterceptor
{
    /** @var int[] list of product IDs with listing */
    protected $productsWithListing = [];

    /**
     * Adds hooks.
     *
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup('admin_enqueue_scripts')
            ->setHandler([$this, 'enqueueAssets'])
            ->execute();

        Register::filter()
            ->setGroup('the_posts')
            ->setHandler([$this, 'listProductsWithListing'])
            ->execute();
    }

    /**
     * Enqueues the JavaScript file.
     *
     * @internal
     *
     * @return void
     * @throws Exception
     */
    public function enqueueAssets() : void
    {
        Enqueue::script()
            ->setHandle('gd-marketplaces-products')
            ->setSource(WordPressRepository::getAssetsUrl('js/features/marketplaces/admin/products-page.js'))
            ->setVersion(Configuration::get('mwc.version'))
            ->setCondition([$this, 'shouldEnqueueAssets'])
            ->attachInlineScriptObject('gdMarketplacesProductsList')
            ->attachInlineScriptVariables([
                'productsWithListing' => $this->productsWithListing,
                'i18n'                => [
                    'bulkUnpublishConfirmationMessage' => $this->getUnpublishConfirmationMessage(),
                ],
            ])
            ->setDependencies(['jquery'])
            ->setDeferred(true)
            ->execute();
    }

    /**
     * Fills the list with products that have a Marketplaces listing.
     *
     * @internal
     *
     * @param array|mixed $posts the same post list as this is just a filter callback
     * @return WP_Post[]
     */
    public function listProductsWithListing($posts) : array
    {
        if ($this->shouldEnqueueAssets()) {
            $postIds = array_map(static function ($post) {
                return $post->ID;
            }, $posts);

            $this->productsWithListing = ProductListingsDataStore::listProductsWithListings($postIds);
        }

        return $posts;
    }

    /**
     * Determines if the assets should be loaded on the current page.
     *
     * @internal
     *
     * @return bool
     */
    public function shouldEnqueueAssets() : bool
    {
        try {
            return WordPressRepository::isCurrentScreen('edit-product');
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * Gets the confirmation message to be used in JavaScript.
     *
     * @return string
     */
    protected function getUnpublishConfirmationMessage() : string
    {
        return esc_html__('Are you sure you want to unpublish? All associated Marketplaces listings will remain published or as a draft but will no longer sync content and inventory with WooCommerce.', 'mwc-core');
    }
}
