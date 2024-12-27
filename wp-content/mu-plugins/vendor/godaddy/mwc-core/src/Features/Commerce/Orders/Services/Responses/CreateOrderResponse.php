<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Responses;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Responses\Contracts\CreateOrderResponseContract;

class CreateOrderResponse implements CreateOrderResponseContract
{
    /** @var non-empty-string */
    protected string $remoteId;

    /**
     * Constructor.
     *
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
