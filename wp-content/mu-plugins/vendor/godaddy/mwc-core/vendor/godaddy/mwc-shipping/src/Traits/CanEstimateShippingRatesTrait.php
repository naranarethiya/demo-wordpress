<?php

namespace GoDaddy\WordPress\MWC\Shipping\Traits;

use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayRequestAdapterContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ShipmentContract;
use GoDaddy\WordPress\MWC\Shipping\Exceptions\Contracts\ShippingExceptionContract;
use GoDaddy\WordPress\MWC\Shipping\Models\ShippingRate;

/**
 * Provides methods to an object to estimate shipping rates.
 *
 * @see ShippingRate
 */
trait CanEstimateShippingRatesTrait
{
    use AdaptsRequestsTrait;

    /** @var class-string<GatewayRequestAdapterContract> class name of the adapter */
    protected $estimateRatesShipmentAdapter;

    /**
     * Estimates shipping rates for shipments.
     *
     * @param ShipmentContract[] $shipments
     * @return array<mixed>
     * @throws ShippingExceptionContract
     */
    public function estimate(array $shipments) : array
    {
        return $this->doAdaptedRequest(new $this->estimateRatesShipmentAdapter($shipments));
    }
}
