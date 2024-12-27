<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Gateways;

use Exception;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\Contracts\OrdersGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\CreateOrderInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\ListOrdersByIdInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\OrderOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\OrdersListOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\UpdateOrderInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Adapters\CreateOrderRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Adapters\ListOrdersByIdRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Adapters\UpdateOrderStatusRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Gateways\AbstractGateway;

class OrdersGateway extends AbstractGateway implements OrdersGatewayContract
{
    use CanGetNewInstanceTrait;

    /**
     * {@inheritDoc}
     */
    public function create(CreateOrderInput $input) : OrderOutput
    {
        /** @var OrderOutput $result */
        $result = $this->doAdaptedRequest(CreateOrderRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function updateOrderStatus(UpdateOrderInput $input) : OrderOutput
    {
        /** @var OrderOutput $result */
        $result = $this->doAdaptedRequest(UpdateOrderStatusRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function listById(ListOrdersByIdInput $input) : OrdersListOutput
    {
        /** @var OrdersListOutput $result */
        $result = $this->doAdaptedRequest(ListOrdersByIdRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * Preforms the request and returns the adapted response.
     *
     * @return mixed
     * @throws CommerceExceptionContract
     */
    protected function doAdaptedRequest(AbstractGatewayRequestAdapter $adapter)
    {
        try {
            return parent::doAdaptedRequest($adapter);
        } catch (Exception $exception) {
            if ($exception instanceof CommerceExceptionContract) {
                throw $exception;
            }

            throw new CommerceException($exception->getMessage(), $exception);
        }
    }
}
