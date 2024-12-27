<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\LineItem;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\MappingServiceContract;

interface LineItemMappingServiceContract extends MappingServiceContract
{
    /**
     * {@inheritDoc}
     * @param LineItem $model
     */
    public function getRemoteId(object $model) : ?string;

    /**
     * {@inheritDoc}
     * @param LineItem $model
     * @param string $remoteId
     */
    public function saveRemoteId(object $model, string $remoteId) : void;
}
