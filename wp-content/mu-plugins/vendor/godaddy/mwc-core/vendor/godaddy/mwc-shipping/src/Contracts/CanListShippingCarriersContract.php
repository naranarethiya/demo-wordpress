<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

use GoDaddy\WordPress\MWC\Shipping\Exceptions\Contracts\ShippingExceptionContract;

interface CanListShippingCarriersContract
{
    /**
     * Retrieves list of carriers.
     *
     * @param ListCarriersOperationContract $operation
     * @return ListCarriersOperationContract
     * @throws ShippingExceptionContract
     */
    public function list(ListCarriersOperationContract $operation) : ListCarriersOperationContract;
}
