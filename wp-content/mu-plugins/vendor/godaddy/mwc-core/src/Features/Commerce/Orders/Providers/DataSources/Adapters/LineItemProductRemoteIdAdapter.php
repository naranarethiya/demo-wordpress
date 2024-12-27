<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Contracts\DataObjectAdapterContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * An adapter to get the Commerce productID of a line item product and convert received product IDs into WooCommerce product instances.
 */
class LineItemProductRemoteIdAdapter implements DataObjectAdapterContract
{
    protected ProductsMappingServiceContract $productsMappingService;

    public function __construct(ProductsMappingServiceContract $productsMappingService)
    {
        $this->productsMappingService = $productsMappingService;
    }

    /**
     * Attempts to set the WooCommerce product associated with the given remote product ID.
     *
     * @param non-empty-string|null $source remote product ID
     * @param LineItem|null $lineItem
     */
    public function convertFromSource($source, ?LineItem $lineItem = null) : LineItem
    {
        // no-op for now
        return $lineItem ?? new LineItem();
    }

    /**
     * Gets the remote ID for the given Product instance.
     *
     * @param Product|null $target
     * @return non-empty-string|null
     */
    public function convertToSource($target) : ?string
    {
        if (! $target) {
            return null;
        }

        return $this->productsMappingService->getRemoteId($target) ?: null;
    }
}
