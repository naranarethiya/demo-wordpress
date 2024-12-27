<?php

namespace GoDaddy\WordPress\MWC\Shipping\Traits;

use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayRequestAdapterContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\VoidShippingLabelOperationContract;
use GoDaddy\WordPress\MWC\Shipping\Exceptions\Contracts\ShippingExceptionContract;
use GoDaddy\WordPress\MWC\Shipping\Models\ShippingLabel;

/**
 * Provides methods to an object to void shipping labels.
 *
 * Can be used to fulfill {@see CanVoidShippingLabelsContract} on a subclass of {@see AbstractGateway}.
 *
 * @see ShippingLabel
 */
trait CanVoidShippingLabelsTrait
{
    use AdaptsRequestsTrait;

    /** @var class-string<GatewayRequestAdapterContract> class name of the adapter */
    protected $voidShippingLabelsRequestAdapter;

    /**
     * Voids shipping labels for shipments.
     *
     * @param VoidShippingLabelOperationContract $operation
     * @return VoidShippingLabelOperationContract
     * @throws ShippingExceptionContract
     */
    public function void(VoidShippingLabelOperationContract $operation) : VoidShippingLabelOperationContract
    {
        return $this->doAdaptedRequest(new $this->voidShippingLabelsRequestAdapter($operation));
    }
}
