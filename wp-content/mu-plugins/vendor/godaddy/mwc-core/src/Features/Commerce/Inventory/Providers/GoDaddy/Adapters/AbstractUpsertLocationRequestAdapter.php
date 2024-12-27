<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Location;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\UpsertLocationInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Http\Requests\Request;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Traits\CanConvertLocationResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;

abstract class AbstractUpsertLocationRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanGetNewInstanceTrait;
    use CanConvertLocationResponseTrait;

    protected UpsertLocationInput $input;

    /**
     * @param UpsertLocationInput $input
     */
    public function __construct(UpsertLocationInput $input)
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
        $data = ArrayHelper::wrap(ArrayHelper::get(ArrayHelper::wrap($response->getBody()), 'inventoryLocation', []));

        return $this->convertLocationResponse($data);
    }

    /**
     * Gets the base upsert request class.
     *
     * @return Request
     */
    protected function getBaseRequest() : Request
    {
        return Request::withAuth()->setStoreId($this->input->storeId);
    }
}
