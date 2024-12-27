<?php

namespace GoDaddy\WordPress\MWC\Shipping\Traits;

use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayRequestAdapterContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\GetTrackingStatusOperationContract;
use GoDaddy\WordPress\MWC\Shipping\Exceptions\Contracts\ShippingExceptionContract;

/**
 * Provides methods to an object to get shipment tracking status.
 *
 * Can be used to fulfill {@see CanGetTrackingStatusContract} on a subclass of {@see AbstractGateway}.
 */
trait CanGetTrackingStatusTrait
{
    use AdaptsRequestsTrait;

    /** @var class-string<GatewayRequestAdapterContract> class name of the adapter */
    protected $trackingStatusRequestAdapter;

    /**
     * Gets shipment tracking status.
     *
     * @param GetTrackingStatusOperationContract $shipment
     * @return GetTrackingStatusOperationContract
     * @throws ShippingExceptionContract
     */
    public function status(GetTrackingStatusOperationContract $shipment) : GetTrackingStatusOperationContract
    {
        /** @var GatewayRequestAdapterContract $adapter */
        $adapter = new $this->trackingStatusRequestAdapter($shipment);

        return $this->doAdaptedRequest($adapter);
    }
}
