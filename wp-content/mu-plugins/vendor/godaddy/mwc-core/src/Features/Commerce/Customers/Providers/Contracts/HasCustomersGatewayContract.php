<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\Contracts;

interface HasCustomersGatewayContract
{
    public function customers() : CustomersGatewayContract;
}
