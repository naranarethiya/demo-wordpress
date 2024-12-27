<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts;

/**
 * Contract for reading products by SKU operations.
 */
interface ReadProductBySkuOperationContract
{
    /**
     * Sets the SKU.
     *
     * @param string $value
     * @return ReadProductBySkuOperationContract
     */
    public function setSku(string $value) : ReadProductBySkuOperationContract;

    /**
     * Gets the SKU.
     *
     * @return string
     */
    public function getSku() : string;
}
