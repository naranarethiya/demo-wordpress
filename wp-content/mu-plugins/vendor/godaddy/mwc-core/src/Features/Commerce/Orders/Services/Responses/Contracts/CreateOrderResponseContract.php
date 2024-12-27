<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Responses\Contracts;

interface CreateOrderResponseContract
{
    /**
     * Gets the order's remote UUID.
     *
     * @return non-empty-string
     */
    public function getRemoteId() : string;
}
