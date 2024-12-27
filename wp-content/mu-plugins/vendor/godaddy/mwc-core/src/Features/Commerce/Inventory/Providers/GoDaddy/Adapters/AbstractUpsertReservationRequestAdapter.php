<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\UpsertReservationInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Http\Requests\Request;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Traits\CanConvertReservationResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;

abstract class AbstractUpsertReservationRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanConvertReservationResponseTrait;
    use CanGetNewInstanceTrait;

    protected UpsertReservationInput $input;

    /**
     * AbstractUpsertReservationRequestAdapter constructor.
     *
     * @param UpsertReservationInput $input
     */
    public function __construct(UpsertReservationInput $input)
    {
        $this->input = $input;
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
