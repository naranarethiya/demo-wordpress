<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\CreateOrderInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\OrderOutput;

interface CanCreateOrdersContract
{
    /**
     * Creates an order.
     *
     * @param CreateOrderInput $input
     * @return OrderOutput
     * @throws CommerceExceptionContract
     */
    public function create(CreateOrderInput $input) : OrderOutput;
}
