<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\RemoteIdBus;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\MappingStrategyContract;

abstract class AbstractTemporaryMappingStrategy implements MappingStrategyContract
{
    /**
     * {@inheritDoc}
     */
    public function saveRemoteId(object $model, string $remoteId) : void
    {
        if (! $bus = $this->getRemoteIdBus($model)) {
            throw CommerceException::getNewInstance('We could not save the remote ID for the given model because the temporary storage location is not available.');
        }

        $bus->set($remoteId);
    }

    /**
     * {@inheritDoc}
     */
    public function getRemoteId(object $model) : ?string
    {
        if ($bus = $this->getRemoteIdBus($model)) {
            return $bus->get();
        }

        return null;
    }

    /**
     * Get an instance of RemoteIdBus with most-identifiable pieces of data available on the model.
     */
    protected function getRemoteIdBus(object $model) : ?RemoteIdBus
    {
        if (! $temporaryKey = $this->getTemporaryKey($model)) {
            return null;
        }

        return RemoteIdBus::withKey($temporaryKey);
    }

    /**
     * Uses the information in the given model to generate a temporary key.
     */
    abstract protected function getTemporaryKey(object $model) : ?string;
}
