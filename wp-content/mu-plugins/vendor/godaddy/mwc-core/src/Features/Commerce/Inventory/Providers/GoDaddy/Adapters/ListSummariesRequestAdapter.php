<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ListSummariesInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Summary;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters\Traits\CanConvertSummaryResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters\Traits\CanGetProductIdsAsStringTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Http\Requests\Request;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;

class ListSummariesRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanConvertSummaryResponseTrait;
    use CanGetNewInstanceTrait;
    use CanGetProductIdsAsStringTrait;

    protected ListSummariesInput $input;

    /**
     * @param ListSummariesInput $input
     */
    public function __construct(ListSummariesInput $input)
    {
        $this->input = $input;
    }

    /**
     * {@inheritDoc}
     *
     * @return Summary[]
     *
     * @throws Exception
     */
    protected function convertResponse(ResponseContract $response) : array
    {
        $summaries = [];

        $responseSummaries = ArrayHelper::wrap(ArrayHelper::get(ArrayHelper::wrap($response->getBody()), 'inventorySummaries'));

        foreach ($responseSummaries as $responseSummary) {
            $summaries[] = $this->convertSummaryResponse($responseSummary);
        }

        return $summaries;
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
            ->setPath('/inventory-summaries');

        if ($queryArgs) {
            $request->setQuery($queryArgs);
        }

        return $request;
    }
}
