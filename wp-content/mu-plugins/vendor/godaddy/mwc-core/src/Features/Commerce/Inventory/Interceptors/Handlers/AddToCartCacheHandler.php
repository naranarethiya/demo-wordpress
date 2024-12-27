<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Interceptors\Handlers;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\ProductInventoryCachingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\ProductMapRepository;

class AddToCartCacheHandler extends AbstractInterceptorHandler
{
    protected ProductMapRepository $productMapRepository;
    protected ProductInventoryCachingServiceContract $inventoryCachingService;

    /**
     * @param ProductMapRepository $productMapRepository
     * @param ProductInventoryCachingServiceContract $inventoryCachingService
     */
    public function __construct(
        ProductMapRepository $productMapRepository,
        ProductInventoryCachingServiceContract $inventoryCachingService
    ) {
        $this->productMapRepository = $productMapRepository;
        $this->inventoryCachingService = $inventoryCachingService;
    }

    /**
     * @param ...$args
     *
     * @return mixed
     */
    public function run(...$args)
    {
        $filterValue = $args[0] ?? null;

        // refresh inventory cache for the product that's being added to the cart
        if ($remoteProductId = $this->productMapRepository->getRemoteId(TypeHelper::int($filterValue, 0))) {
            $this->inventoryCachingService->refreshCache([$remoteProductId]);
        }

        // return the original filter input value as we do not want to alter behavior
        return $filterValue;
    }
}
