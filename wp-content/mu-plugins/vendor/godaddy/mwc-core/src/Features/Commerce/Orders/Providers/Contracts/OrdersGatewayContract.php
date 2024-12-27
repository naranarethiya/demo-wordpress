<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\Contracts;

interface OrdersGatewayContract extends CanCreateOrdersContract, CanUpdateOrderStatusContract, CanListOrdersByIdContract
{
}
