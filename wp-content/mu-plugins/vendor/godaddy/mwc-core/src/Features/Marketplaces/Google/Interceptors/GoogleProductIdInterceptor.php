<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Google\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Exceptions\GoDaddyMarketplacesRequestException;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Google\Services\GoogleProductService;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Http\Requests\GetGoogleProductIdsRequest;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Channel;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Repositories\ChannelRepository;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Repositories\ListingRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\ProductAdapter;
use WC_Product;

/**
 * Interceptor that handles the callback for requesting Google product IDs.
 */
class GoogleProductIdInterceptor extends AbstractInterceptor
{
    /** @var string */
    const FETCH_GOOGLE_PRODUCT_IDS_ACTION = 'mwc_marketplaces_fetch_google_product_ids';

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup(static::FETCH_GOOGLE_PRODUCT_IDS_ACTION)
            ->setHandler([$this, 'fetchGoogleProductIds'])
            ->setArgumentsCount(2)
            ->execute();

        Register::action()
            ->setGroup('woocommerce_update_product')
            ->setHandler([$this, 'maybeRescheduleGoogleProductIdRequest'])
            ->setArgumentsCount(2)
            ->execute();
    }

    /**
     * Executes an API request to GoDaddy Marketplaces to get the Google product IDs for the supplied WooCommerce product IDs.
     *
     * @param int[]|mixed $productIds
     * @param int|mixed $attemptNumber
     * @return void
     */
    public function fetchGoogleProductIds($productIds, $attemptNumber = 1) : void
    {
        try {
            /** @var int[] $productIds */
            $productIds = TypeHelper::array($productIds, []);
            $attemptNumber = TypeHelper::int($attemptNumber, 1);

            $skus = $this->getProductSkus($productIds);
            if (empty($skus)) {
                throw new GoDaddyMarketplacesRequestException(sprintf('No SKUs determined for product IDs: %s.', implode(',', $productIds ?: ['no product IDs set for the request.'])));
            }

            $response = GetGoogleProductIdsRequest::getNewInstance()->setProductSkus($skus)->send();

            if (! $response->isSuccess()) {
                throw new Exception('Failed to retrieve Google product IDs from the API');
            }

            $this->saveGoogleProductIds($productIds, $response);
        } catch (Exception $e) {
            $this->handleError($productIds, $attemptNumber, $e);
        }
    }

    /**
     * Gets product SKUs for the given WooCommerce product IDs.
     *
     * @param int[] $productIds
     *
     * @return string[]
     */
    protected function getProductSkus(array $productIds) : array
    {
        if (empty($productIds)) {
            return [];
        }

        $products = ProductsRepository::query(['included' => $productIds, 'limit' => -1]);
        $productSkus = [];

        foreach ($products as $product) {
            $sku = TypeHelper::string($product->get_sku(), '');

            if (! empty($sku)) {
                $productSkus[] = $sku;
            }
        }

        return $productSkus;
    }

    /**
     * Handles errors that occurred while making the API request.
     *
     * @param int[] $productIds WooCommerce product IDs
     * @param int $attemptNumber current attempt number
     * @param Exception|null $exception previously thrown exception
     * @return void
     */
    protected function handleError(array $productIds, int $attemptNumber, ?Exception $exception = null) : void
    {
        if ($attemptNumber >= TypeHelper::int(Configuration::get('marketplaces.channels.google.productIdRequestMaxAttempts'), 4)) {
            new SentryException('Maximum attempts exceeded for fetching Marketplaces Google product IDs.', $exception);

            return;
        }

        try {
            GoogleProductService::scheduleProductIdRequest($productIds, $attemptNumber + 1);
        } catch (Exception $e) {
            new SentryException('Failed to schedule Google product ID request.', $e);
        }
    }

    /**
     * Saves Google product IDs to WooCommerce products.
     *
     * @see GoogleProductIdInterceptor::fetchGoogleProductIds()
     *
     * @param int[] $productIds
     * @param ResponseContract $response
     * @return void
     */
    protected function saveGoogleProductIds(array $productIds, ResponseContract $response) : void
    {
        $responseBody = $response->getBody();
        /** @var array<string, string> $googleProductIds */
        $googleProductIds = $responseBody ? TypeHelper::array(ArrayHelper::get($responseBody, 'googleProductIds'), []) : null;

        if (empty($googleProductIds)) {
            new SentryException(sprintf('No Google product IDs returned from request for product IDs: %s.', implode(',', $productIds ?: ['no product IDs passed to request.'])));

            return;
        }

        $sourceProducts = ProductsRepository::query(['include' => $productIds, 'limit' => '-1']);

        foreach ($sourceProducts as $sourceProduct) {
            $productSku = TypeHelper::string($sourceProduct->get_sku(), '');
            $googleProductId = ! empty($productSku) ? TypeHelper::string(ArrayHelper::get($googleProductIds, $productSku), '') : null;

            // skip if no SKU, SKU not matched to Google Product ID, or Google Product ID empty
            if (empty($googleProductId)) {
                continue;
            }

            $this->saveGoogleProductId($sourceProduct, $googleProductId);
        }
    }

    /**
     * Saves a Google Product Id to a WooCommerce product.
     *
     * @see GoogleProductIdInterceptor::saveGoogleProductIds()
     *
     * @param WC_Product $sourceProduct
     * @param string $googleProductId
     * @return void
     */
    protected function saveGoogleProductId(WC_Product $sourceProduct, string $googleProductId) : void
    {
        try {
            $adapter = ProductAdapter::getNewInstance($sourceProduct);

            $nativeProduct = $adapter->convertFromSource();
            $nativeProduct->setMarketplacesGoogleProductId($googleProductId);

            $adapter->convertToSource($nativeProduct)->save_meta_data();
        } catch (Exception $exception) {
            new SentryException("Could not set Google product ID for product #{$sourceProduct->get_id()} ({$sourceProduct->get_sku()}).", $exception);
        }
    }

    /**
     * May schedule a new {@see GoogleProductService::scheduleProductIdRequest()} if a product has a Google listing but no Google product ID.
     *
     * Runs whenever a product is updated. This is here to catch cases where the Google product ID request wasn't scheduled correctly.
     *
     * @internal
     *
     * @param int|mixed $productId
     * @param WC_Product|mixed $sourceProduct
     * @return void
     */
    public function maybeRescheduleGoogleProductIdRequest($productId, $sourceProduct) : void
    {
        if (! is_numeric($productId) || ! $sourceProduct instanceof WC_Product) {
            return;
        }

        if (! ChannelRepository::isConnected(Channel::TYPE_GOOGLE)) {
            return;
        }

        try {
            $nativeProduct = ProductAdapter::getNewInstance($sourceProduct)->convertFromSource();
            $googleListings = ListingRepository::getProductListingsByChannelType($nativeProduct, Channel::TYPE_GOOGLE);
            $googleProductId = $nativeProduct->getMarketplacesGoogleProductId();

            if (empty($googleListings) || ! empty($googleProductId) || 'yes' === $sourceProduct->get_meta('_gd_marketplaces_has_requested_google_product_ids')) {
                return;
            }

            GoogleProductService::scheduleProductIdRequest([(int) $productId]);

            $sourceProduct->add_meta_data('_gd_marketplaces_has_requested_google_product_ids', 'yes');
            $sourceProduct->save_meta_data();
        } catch (Exception $exception) {
            return;
        }
    }
}
