<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Shipping\Providers\ShipEngine\Adapters;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Shipping\Providers\ShipEngine\DataSources\Adapters\CarrierAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Shipping\Providers\ShipEngine\Http\Request;
use GoDaddy\WordPress\MWC\Shipping\Adapters\AbstractGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ListCarriersOperationContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\CarrierContract;

class ListCarriersRequestAdapter extends AbstractGatewayRequestAdapter
{
    protected ListCarriersOperationContract $operation;

    public function __construct(ListCarriersOperationContract $operation)
    {
        $this->operation = $operation;
    }

    /** {@inheritDoc} */
    public function convertFromSource() : RequestContract
    {
        return Request::withAuth()
            ->setPath('/shipping/proxy/shipengine/v1/carriers')
            ->setMethod('get')
            ->setQuery([
                'externalAccountId' => $this->operation->getAccount()->getId(),
            ]);
    }

    /** {@inheritDoc} */
    protected function convertResponse(ResponseContract $response)
    {
        return $this->operation->setCarriers(...$this->getCarriersFromResponse($response));
    }

    /**
     * Creates a list of {@see CarrierContract} instances using data from the response.
     *
     * @param ResponseContract $response
     * @return CarrierContract[]
     */
    protected function getCarriersFromResponse(ResponseContract $response) : array
    {
        $carriers = [];

        foreach (ArrayHelper::getArrayValueForKey(ArrayHelper::wrap($response->getBody()), 'carriers') as $data) {
            if (ArrayHelper::accessible($data)) {
                $carriers[] = CarrierAdapter::getNewInstance(ArrayHelper::wrap($data))->convertFromSource();
            }
        }

        return $carriers;
    }
}
