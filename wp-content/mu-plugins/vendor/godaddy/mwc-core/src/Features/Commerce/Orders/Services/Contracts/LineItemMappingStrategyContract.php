<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts;

use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\MappingStrategyContract;

interface LineItemMappingStrategyContract extends MappingStrategyContract
{
    /**
     * {@inheritDoc}
     * @param LineItem $model
     * @return string|null
     */
    public function getRemoteId(object $model) : ?string;

    /**
     * {@inheritDoc}
     * @param LineItem $model
     * @param string $remoteId
     * @return void
     */
    public function saveRemoteId(object $model, string $remoteId) : void;
}
