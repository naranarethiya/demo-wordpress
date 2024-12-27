<?php

namespace GoDaddy\WordPress\MWC\Core\Stores\Repositories;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Stores\Exceptions\RegisterStoreException;
use GoDaddy\WordPress\MWC\Common\Stores\Repositories\AbstractStoreRepository;

/**
 * Store repository for the Managed WordPress platform.
 */
class StoreRepository extends AbstractStoreRepository
{
    /**
     * {@inheritDoc}
     */
    public function determineDefaultStoreId() : ?string
    {
        $defaultStoreId = defined('GD_COMMERCE_DEFAULT_STORE_ID') ? GD_COMMERCE_DEFAULT_STORE_ID : null;

        return TypeHelper::string($defaultStoreId, '') ?: null;
    }

    /**
     * {@inheritDoc}
     *
     * @throws RegisterStoreException
     */
    public function registerStore(string $storeId, string $businessId) : void
    {
        throw new RegisterStoreException('registerStore is not implemented for the Managed WordPress platform.');
    }
}
