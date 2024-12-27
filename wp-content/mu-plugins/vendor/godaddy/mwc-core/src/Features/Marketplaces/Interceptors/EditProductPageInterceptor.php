<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\ProductAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;
use WC_Product;

/**
 * Handles edit product page assets and requests.
 */
class EditProductPageInterceptor extends AbstractInterceptor
{
    /**
     * Registers assets for the Edit Product page.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks()
    {
        Register::action()
            ->setGroup('admin_enqueue_scripts')
            ->setHandler([$this, 'enqueueAssets'])
            ->setCondition([$this, 'shouldEnqueueAssets'])
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
            ->setHandle('gd-marketplaces-product-edit')
            ->setSource(WordPressRepository::getAssetsUrl('js/features/marketplaces/admin/product-edit.js'))
            ->setVersion(Configuration::get('mwc.version'))
            ->setDependencies(['jquery'])
            ->attachInlineScriptObject('gdMarketplacesProductEdit')
            ->attachInlineScriptVariables([
                'productHasListings' => $this->currentProductHasListings(),
                'ajaxUrl'            => admin_url('admin-ajax.php'),
                'createDraftAction'  => CreateDraftListingAjaxInterceptor::CREATE_DRAFT_LISTING_ACTION,
                'createDraftNonce'   => wp_create_nonce(CreateDraftListingAjaxInterceptor::CREATE_DRAFT_LISTING_ACTION),
                'productId'          => get_the_ID(),
                'i18n'               => [
                    'unpublishConfirmationMessage'  => $this->getUnpublishConfirmationMessage(),
                    'createDraftGenericError'       => __('Failed to create a draft listing.', 'mwc-core'),
                    'emptySkuNotAllowedWithListing' => __('Products with Marketplaces listings cannot have an empty SKU.', 'mwc-core'),
                ],
            ])
            ->setDeferred(true)
            ->execute();
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
        return 'edit' === ArrayHelper::get($_GET, 'action') && 'product' === get_post_type(ArrayHelper::get($_GET, 'post'));
    }

    /**
     * Returns the confirmation message to be used in JavaScript.
     *
     * @return string
     */
    protected function getUnpublishConfirmationMessage() : string
    {
        return esc_html__('Are you sure you want to unpublish? All associated Marketplaces listings will remain published or as a draft but will no longer sync content and inventory with WooCommerce.', 'mwc-core');
    }

    /**
     * Determines if the product being edited has any listings.
     *
     * @return bool
     */
    protected function currentProductHasListings() : bool
    {
        // To prevent unnecessary queries if the JS isn't being loaded on the current page.
        if (! $this->shouldEnqueueAssets()) {
            return false;
        }

        try {
            return ! empty($this->getCurrentProduct()->getMarketplacesListings());
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * Retrieves the current product model.
     *
     * @return Product
     * @throws Exception
     */
    protected function getCurrentProduct() : Product
    {
        $wcProduct = wc_get_product(ArrayHelper::get($_GET, 'post'));
        if (! $wcProduct instanceof WC_Product) {
            throw new SentryException('Failed to retrieve WC_Product on Edit Product page.');
        }

        return ProductAdapter::getNewInstance($wcProduct)->convertFromSource();
    }
}
