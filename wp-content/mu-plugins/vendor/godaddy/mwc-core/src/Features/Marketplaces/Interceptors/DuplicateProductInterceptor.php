<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\ProductAdapter;
use WC_Admin_Duplicate_Product;
use WC_Product;

/**
 * Hooks into WooCommerce product duplication to handle Marketplaces data.
 */
class DuplicateProductInterceptor extends AbstractInterceptor
{
    /**
     * Adds hooks.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks() : void
    {
        /* @see WC_Admin_Duplicate_Product::product_duplicate() */
        Register::action()
            ->setGroup('woocommerce_product_duplicate_before_save')
            ->setHandler([$this, 'removeMarketplacesDataFromDuplicatedProduct'])
            ->execute();
    }

    /**
     * Removes Marketplaces data from a product duplicate when it's created.
     *
     * @internal
     *
     * @param WC_Product|mixed $duplicatedProduct
     * @return void
     */
    public function removeMarketplacesDataFromDuplicatedProduct($duplicatedProduct) : void
    {
        if (! $duplicatedProduct instanceof WC_Product) {
            return;
        }

        $marketplacesMetaDataKeys = [
            ProductAdapter::MARKETPLACES_LISTINGS_META_KEY,
            // @NOTE The following meta keys are deliberately allowed here, as the merchant is cloning a product which
            // may have the same characteristics in regard to brand and condition. If adding more Marketplaces meta keys
            // in the product adapter, consider adding those here via constants. {@unfulvio 2022-10-24}
            // ProductAdapter::MARKETPLACES_BRAND_META_KEY,
            // ProductAdapter::MARKETPLACES_CONDITION_META_KEY,
        ];

        // no need to save metadata as the current action runs before the save method is called
        foreach ($marketplacesMetaDataKeys as $metaDataKey) {
            $duplicatedProduct->delete_meta_data($metaDataKey);
        }
    }
}
