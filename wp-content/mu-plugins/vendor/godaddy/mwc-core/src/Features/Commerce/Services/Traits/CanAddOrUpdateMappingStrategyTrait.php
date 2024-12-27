<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Traits;

/**
 * Modifies the save logic to allow overwriting a mapping.
 *
 * It'll only add a mapping if it does not exist or update the existing mapping if the remote ID has changed.
 *
 * @see AbstractMappingStrategy
 * @see AbstractResourceMapRepository::addOrUpdateRemoteId()
 */
trait CanAddOrUpdateMappingStrategyTrait
{
    /**
     * {@inheritDoc}
     */
    protected function saveMappingUsingRepository(int $localId, string $remoteId) : void
    {
        $this->resourceMapRepository->addOrUpdateRemoteId($localId, $remoteId);
    }
}
