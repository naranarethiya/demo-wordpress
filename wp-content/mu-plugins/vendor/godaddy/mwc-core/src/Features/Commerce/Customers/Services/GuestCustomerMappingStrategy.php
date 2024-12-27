<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Contracts\CustomerMappingStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\GuestCustomer;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractMappingStrategy;

class GuestCustomerMappingStrategy extends AbstractMappingStrategy implements CustomerMappingStrategyContract
{
    /**
     * {@inheritDoc}
     * @param GuestCustomer $model
     */
    public function getRemoteId(object $model) : ?string
    {
        if (! $orderLocalId = $model->getOrderId()) {
            return null;
        }

        return $this->resourceMapRepository->getRemoteId($orderLocalId);
    }

    /**
     * Saves the given remote UUID as the remote ID for the given model.
     *
     * @param GuestCustomer $model
     * @param string $remoteId
     * @throws CommerceExceptionContract
     */
    public function saveRemoteId(object $model, string $remoteId) : void
    {
        $this->saveMapping((int) $model->getOrderId(), $remoteId);
    }
}
