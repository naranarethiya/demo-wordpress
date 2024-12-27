<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\ReadSummaryOperationContract;

class ReadSummaryOperation implements ReadSummaryOperationContract
{
    use CanGetNewInstanceTrait;

    protected int $productId;

    /**
     * @param int $productId
     */
    public function __construct(int $productId)
    {
        $this->productId = $productId;
    }

    /**
     * {@inheritDoc}
     */
    public function getProductId() : int
    {
        return $this->productId;
    }
}
