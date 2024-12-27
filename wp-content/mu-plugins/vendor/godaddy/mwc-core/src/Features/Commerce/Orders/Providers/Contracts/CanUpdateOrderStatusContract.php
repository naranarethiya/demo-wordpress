<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\OrderOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\UpdateOrderInput;

/**
 * Defines the gateway operation used to update an order’s status in the remote service.
 */
interface CanUpdateOrderStatusContract
{
    /**
     * Updates the status of an order.
     *
     * @param UpdateOrderInput $input
     * @return OrderOutput
     * @throws CommerceExceptionContract
     */
    public function updateOrderStatus(UpdateOrderInput $input) : OrderOutput;
}
