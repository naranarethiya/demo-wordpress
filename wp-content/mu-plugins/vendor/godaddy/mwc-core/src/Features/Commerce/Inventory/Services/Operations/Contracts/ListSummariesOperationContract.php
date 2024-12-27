<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts;

/**
 * List summaries operation.
 */
interface ListSummariesOperationContract
{
    /**
     * Gets the product IDs.
     *
     * @return string[]
     */
    public function getProductIds() : array;

    /**
     * Sets the product IDs for this operation.
     *
     * @param string[] $value
     *
     * @return $this
     */
    public function setProductIds(array $value) : self;
}
