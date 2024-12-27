<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Responses;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Responses\Contracts\CreateOrUpdateCustomerResponseContract;

class CreateOrUpdateCustomerResponse implements CreateOrUpdateCustomerResponseContract
{
    /**
     * @var non-empty-string
     */
    protected string $remoteId;

    /**
     * @param non-empty-string $remoteId
     */
    public function __construct(string $remoteId)
    {
        $this->remoteId = $remoteId;
    }

    /**
     * {@inheritDoc}
     */
    public function getRemoteId() : string
    {
        return $this->remoteId;
    }
}
