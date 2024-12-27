<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services;

use GoDaddy\WordPress\MWC\Common\Models\Contracts\CustomerContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Contracts\CustomerMappingStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\RemoteIdBus;

class NewCustomerMappingStrategy implements CustomerMappingStrategyContract
{
    /**
     * {@inheritDoc}
     * @param CustomerContract $model
     * @param string $remoteId
     */
    public function saveRemoteId(object $model, string $remoteId) : void
    {
        if (! $bus = $this->getRemoteIdBus($model)) {
            throw CommerceException::getNewInstance('We could not save the remote ID for the given customer because the temporary storage location is not available.');
        }

        $bus->set($remoteId);
    }

    /**
     * {@inheritDoc}
     * @param CustomerContract $model
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
     *
     * @param CustomerContract $model
     * @return RemoteIdBus|null
     */
    protected function getRemoteIdBus(CustomerContract $model) : ?RemoteIdBus
    {
        if (! $email = $model->getEmail()) {
            return null;
        }

        return RemoteIdBus::withKey(md5($email));
    }
}
