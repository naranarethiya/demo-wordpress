<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Shipping\Providers\ShipEngine\Adapters;

use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Shipping\Providers\ShipEngine\DataSources\Adapters\ShippingRateAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Shipping\Providers\ShipEngine\Http\Request;
use GoDaddy\WordPress\MWC\Shipping\Adapters\AbstractGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Shipping\Contracts\GetShippingRateOperationContract;

class GetShippingRateRequestAdapter extends AbstractGatewayRequestAdapter
{
    /** @var GetShippingRateOperationContract */
    protected $operation;

    public function __construct(GetShippingRateOperationContract $operation)
    {
        $this->operation = $operation;
    }

    /** {@inheritdoc}
     */
    public function convertFromSource() : RequestContract
    {
        return Request::withAuth()
            ->setPath("/shipping/proxy/shipengine/v1/rates/{$this->operation->getShippingRateId()}")
            ->setMethod('get')
            ->setQuery([
                'externalAccountId' => $this->operation->getAccount()->getId(),
            ]);
    }

    /** {@inheritdoc} */
    protected function convertResponse(ResponseContract $response)
    {
        $shippingRate = ShippingRateAdapter::getNewInstance($response->getBody())->convertFromSource();

        return $this->operation->setShippingRate($shippingRate);
    }
}
