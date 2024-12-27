<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Contracts;

use GoDaddy\WordPress\MWC\Common\Models\Contracts\CustomerContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\MappingStrategyContract;

interface CustomerMappingStrategyContract extends MappingStrategyContract
{
    /**
     * {@inheritDoc}
     *
     * @param CustomerContract $model
     */
    public function saveRemoteId(object $model, string $remoteId) : void;

    /**
     * {@inheritDoc}
     *
     * @param CustomerContract $model
     */
    public function getRemoteId(object $model) : ?string;
}
