<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations;

use GoDaddy\WordPress\MWC\Common\Traits\CanSeedTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ReadProductOperationContract;

/**
 * Read product operation.
 */
class ReadProductOperation implements ReadProductOperationContract
{
    use CanSeedTrait;

    /** @var int the product's local WooCommerce ID */
    protected int $localId;

    /**
     * Sets the local ID.
     *
     * @param int $value
     * @return ReadProductOperationContract
     */
    public function setLocalId(int $value) : ReadProductOperationContract
    {
        $this->localId = $value;

        return $this;
    }

    /**
     * Gets the local ID.
     *
     * @return int
     */
    public function getLocalId() : int
    {
        return $this->localId;
    }
}
