<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters;

use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\DeleteLevelInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Http\Requests\Request;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;

class DeleteLevelRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanGetNewInstanceTrait;

    protected DeleteLevelInput $input;

    /**
     * DeleteLevelRequestAdapter constructor.
     *
     * @param DeleteLevelInput $input
     */
    public function __construct(DeleteLevelInput $input)
    {
        $this->input = $input;
    }

    /**
     * This method is no-op.
     */
    protected function convertResponse(ResponseContract $response)
    {
        // no-op
    }

    /**
     * {@inheritDoc}
     */
    public function convertFromSource() : RequestContract
    {
        return Request::withAuth()
            ->setStoreId($this->input->storeId)
            ->setPath('/inventory-levels/'.$this->input->levelId)
            ->setMethod('DELETE');
    }
}
