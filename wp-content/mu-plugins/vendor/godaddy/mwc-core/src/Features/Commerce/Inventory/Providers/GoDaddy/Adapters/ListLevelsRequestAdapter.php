<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Level;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ListLevelsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters\Traits\CanGetProductIdsAsStringTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Http\Requests\Request;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Traits\CanConvertLevelResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;

class ListLevelsRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanConvertLevelResponseTrait;
    use CanGetProductIdsAsStringTrait;
    use CanGetNewInstanceTrait;

    protected ListLevelsInput $input;

    /**
     * @param ListLevelsInput $input
     */
    public function __construct(ListLevelsInput $input)
    {
        $this->input = $input;
    }

    /**
     * {@inheritDoc}
     *
     * @return Level[]
     *
     * @throws Exception
     */
    protected function convertResponse(ResponseContract $response) : array
    {
        $levels = [];

        $responseLevels = ArrayHelper::wrap(ArrayHelper::get(ArrayHelper::wrap($response->getBody()), 'inventoryLevels'));

        foreach ($responseLevels as $responseLevel) {
            $levels[] = $this->convertLevelResponse($responseLevel);
        }

        return $levels;
    }

    /**
     * {@inheritDoc}
     */
    public function convertFromSource() : Request
    {
        $queryArgs = [];
        if ($productIds = $this->getProductIdsAsString($this->input->productIds)) {
            $queryArgs['productIds'] = $productIds;
        }

        $request = Request::withAuth()
            ->setStoreId($this->input->storeId)
            ->setPath('/inventory-levels');

        if ($queryArgs) {
            $request->setQuery($queryArgs);
        }

        return $request;
    }
}
