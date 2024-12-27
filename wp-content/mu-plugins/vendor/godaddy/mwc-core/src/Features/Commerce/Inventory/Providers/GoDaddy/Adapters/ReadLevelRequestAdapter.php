<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Level;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ReadLevelInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Http\Requests\Request;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Traits\CanConvertLevelResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;

class ReadLevelRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanConvertLevelResponseTrait;
    use CanGetNewInstanceTrait;

    protected ReadLevelInput $input;

    /**
     * ReadLevelRequestAdapter constructor.
     *
     * @param ReadLevelInput $input
     */
    public function __construct(ReadLevelInput $input)
    {
        $this->input = $input;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    protected function convertResponse(ResponseContract $response) : Level
    {
        $body = ArrayHelper::wrap(ArrayHelper::get(ArrayHelper::wrap($response->getBody()), 'inventoryLevel'));

        return $this->convertLevelResponse($body);
    }

    /**
     * {@inheritDoc}
     */
    public function convertFromSource() : RequestContract
    {
        return Request::withAuth()
            ->setStoreId($this->input->storeId)
            ->setPath(sprintf('/inventory-levels/%s', $this->input->levelId));
    }
}
