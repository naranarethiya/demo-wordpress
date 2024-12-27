<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\CanConvertToArrayContract;

interface PatchProductOperationContract extends CanConvertToArrayContract
{
    /**
     * Sets the category IDs for the product.
     *
     * @param string[] $categoryIds array of remote category UUIDs
     * @return $this
     */
    public function setCategoryIds(array $categoryIds) : PatchProductOperationContract;

    /**
     * Set the local product ID.
     *
     * @return $this
     */
    public function setLocalProductId(int $localProductId) : PatchProductOperationContract;

    /**
     * Get the local product ID.
     *
     * @return int
     */
    public function getLocalProductId() : int;
}
