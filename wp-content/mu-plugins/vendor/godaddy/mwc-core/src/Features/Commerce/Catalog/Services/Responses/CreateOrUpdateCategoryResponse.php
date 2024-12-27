<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\CreateOrUpdateCategoryResponseContract;

/**
 * Response object for a create or update product category request.
 *
 * @method static static getNewInstance(string $remoteId)
 */
class CreateOrUpdateCategoryResponse implements CreateOrUpdateCategoryResponseContract
{
    use CanGetNewInstanceTrait;

    /** @var non-empty-string */
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
