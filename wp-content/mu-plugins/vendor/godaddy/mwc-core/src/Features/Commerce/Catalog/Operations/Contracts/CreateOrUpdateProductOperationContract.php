<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ChannelIds;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Contract for creating or updating product operations.
 */
interface CreateOrUpdateProductOperationContract
{
    /**
     * Sets the Product model.
     *
     * @param Product $value
     * @return CreateOrUpdateProductOperationContract
     */
    public function setProduct(Product $value) : CreateOrUpdateProductOperationContract;

    /**
     * Gets the Product model.
     *
     * @return Product
     */
    public function getProduct() : Product;

    /**
     * Sets the local WordPress ID.
     *
     * @param int $value
     * @return CreateOrUpdateProductOperationContract
     */
    public function setLocalId(int $value) : CreateOrUpdateProductOperationContract;

    /**
     * Gets the local WordPress ID.
     *
     * @return int
     */
    public function getLocalId() : int;

    /**
     * Sets the ChannelIds DTO for POST and PATCH requests.
     *
     * @param ChannelIds $value
     * @return CreateOrUpdateProductOperationContract
     */
    public function setChannelIds(ChannelIds $value) : CreateOrUpdateProductOperationContract;

    /**
     * Gets the ChannelIds DTO for POST and PATCH requests.
     *
     * @return ChannelIds
     */
    public function getChannelIds() : ChannelIds;
}
