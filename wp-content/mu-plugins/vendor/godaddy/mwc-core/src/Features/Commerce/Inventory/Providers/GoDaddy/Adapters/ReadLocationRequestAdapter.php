<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Location;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ReadLocationInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Http\Requests\Request;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Traits\CanConvertLocationResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;

class ReadLocationRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanGetNewInstanceTrait;
    use CanConvertLocationResponseTrait;

    protected ReadLocationInput $input;

    /**
     * @param ReadLocationInput $input
     */
    public function __construct(ReadLocationInput $input)
    {
        $this->input = $input;
    }

    /**
     * {@inheritDoc}
     *
     * @return Location
     * @throws Exception
     */
    protected function convertResponse(ResponseContract $response) : Location
    {
        $body = ArrayHelper::wrap(ArrayHelper::get(ArrayHelper::wrap($response->getBody()), 'inventoryLocation'));

        return $this->convertLocationResponse($body);
    }

    /**
     * {@inheritDoc}
     */
    public function convertFromSource() : Request
    {
        return Request::withAuth()
                      ->setStoreId($this->input->storeId)
                      ->setPath(sprintf('/inventory-locations/%s', $this->input->locationId));
    }
}
