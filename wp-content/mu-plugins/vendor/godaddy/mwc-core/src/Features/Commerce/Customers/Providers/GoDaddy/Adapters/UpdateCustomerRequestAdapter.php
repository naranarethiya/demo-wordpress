<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\GoDaddy\Adapters;

use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\DataObjects\CustomerOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\DataObjects\UpdateCustomerInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\GoDaddy\Http\Requests\Request;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;

/**
 * @method static static getNewInstance(UpdateCustomerInput $input)
 */
class UpdateCustomerRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanGetNewInstanceTrait;

    protected UpdateCustomerInput $input;

    /**
     * Constructor.
     *
     * @param UpdateCustomerInput $input
     */
    public function __construct(UpdateCustomerInput $input)
    {
        $this->input = $input;
    }

    /**
     * {@inheritDoc}
     */
    public function convertFromSource() : RequestContract
    {
        return Request::withAuth()
            ->setMethod('patch')
            ->setStoreId($this->input->storeId)
            ->setPath('/customers/'.$this->input->customerId)
            ->setBody([
                'customer' => $this->input->customer->toArray(),
            ]);
    }

    /**
     * Converts ResponseContract to CustomerOutput object.
     *
     * @param ResponseContract $response
     * @return CustomerOutput
     */
    protected function convertResponse(ResponseContract $response) : CustomerOutput
    {
        return CustomerOutput::getNewInstance(['customerId' => $this->input->customerId]);
    }
}
