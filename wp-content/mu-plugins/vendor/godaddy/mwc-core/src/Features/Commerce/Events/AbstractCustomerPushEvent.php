<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Payments\Models\Customer;

abstract class AbstractCustomerPushEvent implements EventContract
{
    /** @var Customer[] */
    protected array $customers;

    /**
     * Get customers from this event.
     *
     * @return Customer[]
     */
    public function getCustomers() : array
    {
        return $this->customers;
    }

    /**
     * Set customers in this event.
     *
     * @param Customer[] $customers
     * @return $this
     */
    public function setCustomers(array $customers) : AbstractCustomerPushEvent
    {
        $this->customers = $customers;

        return $this;
    }
}
