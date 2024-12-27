<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\CustomerNote;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractMappingStrategy;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Traits\CanAddOrUpdateMappingStrategyTrait;

class CustomerNoteMappingStrategy extends AbstractMappingStrategy
{
    use CanAddOrUpdateMappingStrategyTrait;

    /**
     * {@inheritDoc}
     * @param CustomerNote $model
     */
    public function getRemoteId(object $model) : ?string
    {
        if (! $orderLocalId = $model->getOrderId()) {
            return null;
        }

        return $this->resourceMapRepository->getRemoteId($orderLocalId);
    }

    /**
     * Saves the given remote UUID as the remote ID for the given note.
     *
     * @param CustomerNote $model
     * @throws CommerceExceptionContract
     */
    public function saveRemoteId(object $model, string $remoteId) : void
    {
        $this->saveMapping((int) $model->getOrderId(), $remoteId);
    }
}
