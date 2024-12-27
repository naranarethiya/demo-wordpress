<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Level;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Summary;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

class LevelAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /**
     * This method is no-op.
     */
    public function convertFromSource()
    {
        // No-op
    }

    /**
     * Returns a populated Level data object based on given product.
     *
     * @param Product|null $product
     * @return Level|null
     */
    public function convertToSource(?Product $product = null) : ?Level
    {
        if (! $product) {
            return null;
        }

        return Level::getNewInstance([
            'quantity' => TypeHelper::float($product->getCurrentStock(), 0.0),
            'summary'  => $this->adaptProductToSummary($product),
        ]);
    }

    /**
     * Returns a populated Summary object based on given product.
     *
     * @param Product $product
     * @return Summary
     */
    protected function adaptProductToSummary(Product $product) : Summary
    {
        return Summary::getNewInstance([
            'lowInventoryThreshold' => $product->getLowStockThreshold(),
            'isBackorderable'       => in_array($product->getBackordersAllowed(), ['yes', 'notify'], true),
        ]);
    }
}
