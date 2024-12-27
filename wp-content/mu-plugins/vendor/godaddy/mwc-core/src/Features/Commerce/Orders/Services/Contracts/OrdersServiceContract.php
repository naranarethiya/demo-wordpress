<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Operations\Contracts\CreateOrderOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Operations\Contracts\ListOrdersByIdOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Operations\Contracts\UpdateOrderOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Responses\Contracts\CreateOrderResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Responses\Contracts\ListOrdersResponseContract;

interface OrdersServiceContract
{
    /**
     * Creates an order using the provided operation.
     *
     * @param CreateOrderOperationContract $operation
     * @return CreateOrderResponseContract
     * @throws CommerceExceptionContract
     */
    public function createOrder(CreateOrderOperationContract $operation) : CreateOrderResponseContract;

    /**
     * Updates an order using the provided operation.
     *
     * @param UpdateOrderOperationContract $operation
     * @return void
     * @throws CommerceExceptionContract
     */
    public function updateOrder(UpdateOrderOperationContract $operation) : void;

    /**
     * Gets a list of orders by their local IDs.
     *
     * @param ListOrdersByIdOperationContract $operation
     *
     * @return ListOrdersResponseContract
     * @throws CommerceExceptionContract
     */
    public function listOrdersById(ListOrdersByIdOperationContract $operation) : ListOrdersResponseContract;
}
