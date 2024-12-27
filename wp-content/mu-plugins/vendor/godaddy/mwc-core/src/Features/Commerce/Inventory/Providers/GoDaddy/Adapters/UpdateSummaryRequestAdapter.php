<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Summary;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\UpsertSummaryInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters\Traits\CanConvertSummaryResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Http\Requests\Request;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;

class UpdateSummaryRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanConvertSummaryResponseTrait;
    use CanGetNewInstanceTrait;

    protected UpsertSummaryInput $input;

    /**
     * @param UpsertSummaryInput $input
     */
    public function __construct(UpsertSummaryInput $input)
    {
        $this->input = $input;
    }

    /**
     * {@inheritDoc}
     *
     * @return Summary
     *
     * @throws Exception
     */
    protected function convertResponse(ResponseContract $response) : Summary
    {
        $data = ArrayHelper::wrap(ArrayHelper::get(ArrayHelper::wrap($response->getBody()), 'inventorySummary', []));

        return $this->convertSummaryResponse($data);
    }

    /**
     * {@inheritDoc}
     */
    public function convertFromSource() : Request
    {
        $summary = $this->input->summary;

        $data = [
            'isBackorderable' => $summary->isBackorderable,
            'maxBackorders'   => $summary->maxBackorders,
            'maxReservations' => $summary->maxReservations,
        ];

        // this can only be patched as a number, not null
        if (! is_null($summary->lowInventoryThreshold)) {
            ArrayHelper::set($data, 'lowInventoryThreshold', $summary->lowInventoryThreshold);
        }

        return Request::withAuth()
            ->setStoreId($this->input->storeId)
            ->setPath('/inventory-summaries/'.$summary->inventorySummaryId)
            ->setMethod('PATCH')
            ->setBody($data);
    }
}
