<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ListLocationsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Location;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Http\Requests\Request;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Traits\CanConvertLocationResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;

class ListLocationsRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanConvertLocationResponseTrait;
    use CanGetNewInstanceTrait;

    protected ListLocationsInput $input;

    /**
     * @param ListLocationsInput $input
     */
    public function __construct(ListLocationsInput $input)
    {
        $this->input = $input;
    }

    /**
     * {@inheritDoc}
     *
     * @return Location[]
     *
     * @throws Exception
     */
    protected function convertResponse(ResponseContract $response) : array
    {
        $locations = [];

        $responseLocations = ArrayHelper::wrap(ArrayHelper::get(ArrayHelper::wrap($response->getBody()), 'inventoryLocations'));

        foreach ($responseLocations as $responseLocation) {
            $locations[] = $this->convertLocationResponse($responseLocation);
        }

        return $locations;
    }

    /**
     * {@inheritDoc}
     */
    public function convertFromSource() : Request
    {
        return Request::withAuth()
            ->setStoreId($this->input->storeId)
            ->setPath('/inventory-locations/');
    }
}
