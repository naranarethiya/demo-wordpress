<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations;

use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Common\Traits\CanSeedTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\PatchProductOperationContract;

/**
 * PATCH product operation class.
 *
 * The properties in this class represent parameters that _can_ get patched via the API. When performing a PATCH operation,
 * only the properties we want to update will be set in the class. In other words: there may be properties that never
 * get set if we're not intending to update them.
 */
class PatchProductOperation implements PatchProductOperationContract
{
    use CanSeedTrait;
    use CanConvertToArrayTrait;

    /** @var string[] remote category UUIDs */
    protected array $categoryIds;

    /** @var int local ID of product to patch */
    protected int $localProductId;

    /**
     * Sets an array of remote category UUIDs.
     *
     * @param string[] $categoryIds remote category UUIDs
     * @return $this
     */
    public function setCategoryIds(array $categoryIds) : PatchProductOperation
    {
        $this->categoryIds = $categoryIds;

        return $this;
    }

    /**
     * Sets the local WooCommerce product ID.
     *
     * @param int $localProductId
     * @return $this
     */
    public function setLocalProductId(int $localProductId) : PatchProductOperation
    {
        $this->localProductId = $localProductId;

        return $this;
    }

    /**
     * Gets the local WooCommerce product ID.
     *
     * @return int
     */
    public function getLocalProductId() : int
    {
        return $this->localProductId;
    }
}
