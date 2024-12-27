<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Traits;

/**
 * Modifies the save logic to only add a mapping if it does not exist, yet.
 *
 * @see AbstractMappingStrategy
 */
trait CanAddMappingStrategyTrait
{
    /**
     * {@inheritDoc}
     */
    protected function saveMappingUsingRepository(int $localId, string $remoteId) : void
    {
        if (! $this->resourceMapRepository->getRemoteId($localId)) {
            $this->resourceMapRepository->add($localId, $remoteId);
        }
    }
}
