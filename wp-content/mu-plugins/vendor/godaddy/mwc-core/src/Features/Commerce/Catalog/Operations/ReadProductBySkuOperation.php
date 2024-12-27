<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations;

use GoDaddy\WordPress\MWC\Common\Traits\CanSeedTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ReadProductBySkuOperationContract;

/**
 * Read product by SKU operation.
 */
class ReadProductBySkuOperation implements ReadProductBySkuOperationContract
{
    use CanSeedTrait;

    /** @var string the SKU to find the product by */
    protected string $sku;

    /**
     * Sets the SKU.
     *
     * @param string $value
     * @return $this
     */
    public function setSku(string $value) : ReadProductBySkuOperationContract
    {
        $this->sku = $value;

        return $this;
    }

    /**
     * Gets the SKU.
     *
     * @return string
     */
    public function getSku() : string
    {
        return $this->sku;
    }
}
