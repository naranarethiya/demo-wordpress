<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts;

interface HasSummariesContract
{
    /**
     * Returns Summaries gateway.
     *
     * @return SummariesGatewayContract
     */
    public function summaries() : SummariesGatewayContract;
}
