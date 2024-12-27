<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\CrossSellProductsInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\BatchListProductsByLocalIdService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Traits\CanDetermineShouldReadProductsTrait;

/**
 * Handler for {@see CrossSellProductsInterceptor}. This is responsible for pre-warming the cache for cross-sell
 * products that are displayed on the front-end cart page, to prevent N+1 issues.
 */
class CrossSellProductsHandler extends AbstractInterceptorHandler
{
    use CanDetermineShouldReadProductsTrait;

    protected BatchListProductsByLocalIdService $batchListProductsByLocalIdService;

    /**
     * Constructor.
     *
     * @param BatchListProductsByLocalIdService $batchListProductsByLocalIdService
     */
    public function __construct(BatchListProductsByLocalIdService $batchListProductsByLocalIdService)
    {
        $this->batchListProductsByLocalIdService = $batchListProductsByLocalIdService;
    }

    /**
     * {@inheritDoc}
     */
    public function run(...$args)
    {
        $wooProductIds = TypeHelper::arrayOfIntegers($args[0] ?? [], false);

        if (! empty($wooProductIds) && $this->shouldReadProducts()) {
            /*
             * We don't actually need to do anything with the results; we're just pre-warming the cache so that
             * future "get product" calls will read from cache instead of doing a separate API request.
             */
            $this->batchListProductsByLocalIdService->batchListByLocalIds($wooProductIds);
        }

        // we want to return the unmodified argument, as we're not actually intending to _change_ the values
        return $args[0] ?? [];
    }
}
