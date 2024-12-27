<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Contracts\HasLocalIdsContract;

/**
 * A trait that fulfills the contract defined in the {@see HasLocalIdsContract} interface.
 */
trait HasLocalIdsTrait
{
    /** @var int[] Local resource IDs */
    protected array $localIds = [];

    /**
     * Sets the local resource IDs.
     *
     * @param int[] $ids
     *
     * @return $this
     */
    public function setLocalIds(array $ids)
    {
        $this->localIds = $ids;

        return $this;
    }

    /**
     * Gets the local resource IDs.
     *
     * @return int[] $ids
     */
    public function getLocalIds() : array
    {
        return $this->localIds;
    }
}
