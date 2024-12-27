<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations;

use GoDaddy\WordPress\MWC\Common\Traits\CanSeedTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ReadCategoryOperationContract;

/**
 * Read category operation.
 */
class ReadCategoryOperation implements ReadCategoryOperationContract
{
    use CanSeedTrait;

    protected int $localId;

    /**
     * {@inheritDoc}
     */
    public function setLocalId(int $value) : ReadCategoryOperationContract
    {
        $this->localId = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getLocalId() : int
    {
        return $this->localId;
    }
}
