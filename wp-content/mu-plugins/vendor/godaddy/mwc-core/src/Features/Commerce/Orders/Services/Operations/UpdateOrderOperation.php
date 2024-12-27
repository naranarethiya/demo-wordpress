<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Operations;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Operations\Contracts\UpdateOrderOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\HasRequiredOrderTrait;

class UpdateOrderOperation implements UpdateOrderOperationContract
{
    use HasRequiredOrderTrait;

    protected string $newWooCommerceOrderStatus;

    protected string $oldWooCommerceOrderStatus;

    /**
     * {@inheritDoc}
     */
    public function getNewWooCommerceOrderStatus() : string
    {
        return $this->newWooCommerceOrderStatus;
    }

    /**
     * {@inheritDoc}
     */
    public function setNewWooCommerceOrderStatus(string $value)
    {
        $this->newWooCommerceOrderStatus = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getOldWooCommerceOrderStatus() : string
    {
        return $this->oldWooCommerceOrderStatus;
    }

    /**
     * {@inheritDoc}
     */
    public function setOldWooCommerceOrderStatus(string $value)
    {
        $this->oldWooCommerceOrderStatus = $value;

        return $this;
    }
}
