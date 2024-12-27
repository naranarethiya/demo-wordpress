<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Gateways;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\SummariesGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ListSummariesInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Summary;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\UpsertSummaryInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters\ListSummariesRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters\UpdateSummaryRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Gateways\AbstractGateway;

class SummariesGateway extends AbstractGateway implements SummariesGatewayContract
{
    use CanGetNewInstanceTrait;

    /**
     * {@inheritDoc}
     */
    public function list(ListSummariesInput $input) : array
    {
        /** @var Summary[] $result */
        $result = $this->doAdaptedRequest(ListSummariesRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function update(UpsertSummaryInput $input) : Summary
    {
        /** @var Summary $result */
        $result = $this->doAdaptedRequest(UpdateSummaryRequestAdapter::getNewInstance($input));

        return $result;
    }
}
