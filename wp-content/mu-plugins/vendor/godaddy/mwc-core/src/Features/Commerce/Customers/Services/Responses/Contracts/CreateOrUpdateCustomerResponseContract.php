<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Responses\Contracts;

interface CreateOrUpdateCustomerResponseContract
{
    /**
     * Gets the customer's remote UUID.
     *
     * @return non-empty-string
     */
    public function getRemoteId() : string;
}
