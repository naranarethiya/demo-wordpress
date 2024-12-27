<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\CheckForDeletedProductHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\VariantUpdateDeleteHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;

/**
 * Trait for reconciling local and remote variants for a product.
 *
 * If the remote product has been deleted or updated, the local product will be deleted or updated.
 * If the remote product has variants, the local variants will be updated or deleted.
 */
trait CanReconcileRemoteProductsTrait
{
    protected CheckForDeletedProductHelper $checkForDeletedProductHelper;

    protected VariantUpdateDeleteHelper $variantUpdateDeleteHelper;

    /**
     * Reconciles local product (including variants) with remote product.
     *
     * @param int $productId
     * @return void
     * @throws Exception|CommerceExceptionContract
     */
    protected function reconcileRemoteProducts(int $productId) : void
    {
        // Deletes the post cache for a single product to ensure we retrieve fresh data.
        wp_cache_delete($productId, 'posts');

        /**
         * Checks if the product was deleted upstream. If it has, this will halt the rest of the page execution.
         *
         * If the product still exists, the cache will be refreshed with the product details.
         */
        $product = $this->checkForDeletedProductHelper->deleteLocalProductIfDeletedUpstream($productId);

        /**
         * Ensures local variants match what's upstream.
         * If the product has variants, this will update or delete them as necessary.
         *
         * Note: The Commerce API will return variants even if they have been deleted, therefore `$product->variants`
         *       will be non-null if the product ever had variants.
         */
        if ($product && $product->variants) {
            $this->variantUpdateDeleteHelper->reconcileVariantsForProductByPostId($productId);
        }
    }
}
