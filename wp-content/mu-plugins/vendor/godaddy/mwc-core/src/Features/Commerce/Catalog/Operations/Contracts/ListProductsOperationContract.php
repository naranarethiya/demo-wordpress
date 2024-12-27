<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\CanConvertToArrayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Operations\Contracts\ListRemoteResourcesOperationContract;

interface ListProductsOperationContract extends ListRemoteResourcesOperationContract, CanConvertToArrayContract,
    HasPageSizeContract, HasPageTokenContract, HasSortingContract, HasAltIdFilterContract, HasParentIdContract
{
    /**
     * Set the include deleted flag.
     *
     * @deprecated includeDeleted no longer supported by the Catalog API.
     * @param ?bool $includeDeleted
     * @return $this
     */
    public function setIncludeDeleted(?bool $includeDeleted);

    /**
     * Get the include deleted flag.
     *
     * @deprecated includeDeleted no longer supported by the Catalog API.
     * @return ?bool
     */
    public function getIncludeDeleted() : ?bool;

    /**
     * Sets the include child products flag.
     *
     * @param bool|null $includeChildProducts
     * @return $this
     */
    public function setIncludeChildProducts(?bool $includeChildProducts) : ListProductsOperationContract;

    /**
     * Gets the include child products flag.
     *
     * @return bool|null
     */
    public function getIncludeChildProducts() : ?bool;

    /**
     * Set the category ID.
     *
     * @param ?int $localCategoryId
     * @return $this
     */
    public function setLocalCategoryId(?int $localCategoryId);

    /**
     * Get the category ID.
     *
     * @return ?int
     */
    public function getLocalCategoryId() : ?int;

    /**
     * Set the channel ID.
     *
     * @param ?string $channelId
     * @return $this
     */
    public function setChannelId(?string $channelId);

    /**
     * Get the channel ID.
     *
     * @return ?string
     */
    public function getChannelId() : ?string;

    /**
     * Set the SKU.
     *
     * @param ?string $sku
     * @return $this
     */
    public function setSku(?string $sku);

    /**
     * Get the SKU.
     *
     * @return ?string
     */
    public function getSku() : ?string;

    /**
     * Set the name.
     *
     * @param ?string $name
     * @return $this
     */
    public function setName(?string $name);

    /**
     * Get the name.
     *
     * @return ?string
     */
    public function getName() : ?string;

    /**
     * Set the type.
     *
     * @param ?string $type
     * @return ListProductsOperationContract
     */
    public function setType(?string $type) : ListProductsOperationContract;

    /**
     * Get the type.
     *
     * @return ?string
     */
    public function getType() : ?string;
}
