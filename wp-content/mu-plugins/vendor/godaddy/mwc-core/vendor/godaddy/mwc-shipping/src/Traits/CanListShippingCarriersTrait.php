<?php

namespace GoDaddy\WordPress\MWC\Shipping\Traits;

use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayRequestAdapterContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ListCarriersOperationContract;
use GoDaddy\WordPress\MWC\Shipping\Exceptions\Contracts\ShippingExceptionContract;

/**
 * Can be used to fulfill {@see CanListShippingCarriersContract} on a subclass of {@see AbstractGateway}.
 */
trait CanListShippingCarriersTrait
{
    use AdaptsRequestsTrait;

    /** @var class-string<GatewayRequestAdapterContract> */
    protected $listCarriersRequestAdapter;

    /**
     * Retrieves list of carriers.
     *
     * @param ListCarriersOperationContract $operation
     * @return ListCarriersOperationContract
     * @throws ShippingExceptionContract
     */
    public function list(ListCarriersOperationContract $operation) : ListCarriersOperationContract
    {
        return $this->doAdaptedRequest(new $this->listCarriersRequestAdapter($operation));
    }
}
