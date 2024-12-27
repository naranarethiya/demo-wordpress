<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Http\Requests\UpdateProductSkuRequest;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\ProductAdapter;

/**
 * Intercepts general product changes for Marketplaces-related behavior.
 */
class ProductInterceptor extends AbstractInterceptor
{
    /**
     * Adds hooks.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::filter()
            ->setGroup('update_post_metadata')
            ->setHandler([$this, 'onUpdatedSku'])
            ->setPriority(PHP_INT_MAX)
            ->setArgumentsCount(5)
            ->execute();
    }

    /**
     * Issues a request to Marketplaces when a product's SKU changes.
     *
     * @param bool|null|mixed $skipUpdating
     * @param int|mixed $productId
     * @param int|string $metaKey
     * @param string|mixed $newValue
     * @param string|mixed $oldValue
     * @return bool|null|mixed
     */
    public function onUpdatedSku($skipUpdating, $productId, $metaKey, $newValue, $oldValue)
    {
        $productId = TypeHelper::int($productId, 0);

        // only handle products SKU meta when the value changed
        if (null !== $skipUpdating || '_sku' !== $metaKey || ! is_string($newValue) || $productId <= 0 || 'product' !== get_post_type($productId)) {
            return $skipUpdating;
        }

        try {
            $sourceProduct = ProductsRepository::get($productId);

            if (! $sourceProduct) {
                return null;
            }

            $coreProduct = ProductAdapter::getNewInstance($sourceProduct)->convertFromSource();
            $oldSku = $coreProduct->getSku();
            $hasListings = ! empty($coreProduct->getMarketplacesListings());

            // short-circuit option update if the product has listing(s) but merchant is trying to set empty SKU
            if ($hasListings && empty($newValue) && ! empty($oldSku)) {
                return $oldSku;
            }

            // only send a request if the SKU has changed and the product has a Marketplaces listing
            if (! $oldSku || ! $hasListings || $oldSku === $newValue) {
                return null;
            }

            UpdateProductSkuRequest::getNewInstance()
                ->setProductId($productId)
                ->setOldSku($oldSku)
                ->setNewSku($newValue)
                ->send();
        } catch (Exception $exception) {
            new SentryException(sprintf('Could not send SKU update request for product #%s.', $productId), $exception);
        }

        return null;
    }
}
