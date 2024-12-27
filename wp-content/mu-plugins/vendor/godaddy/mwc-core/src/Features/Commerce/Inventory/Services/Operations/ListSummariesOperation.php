<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations;

use GoDaddy\WordPress\MWC\Common\Traits\CanSeedTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\ListSummariesOperationContract;

class ListSummariesOperation implements ListSummariesOperationContract
{
    use CanSeedTrait;

    /** @var string[] remote product IDs */
    protected array $productIds = [];

    /**
     * {@inheritDoc}
     */
    public function getProductIds() : array
    {
        return $this->productIds;
    }

    /**
     * {@inheritDoc}
     */
    public function setProductIds(array $value) : ListSummariesOperationContract
    {
        $this->productIds = $value;

        return $this;
    }
}
