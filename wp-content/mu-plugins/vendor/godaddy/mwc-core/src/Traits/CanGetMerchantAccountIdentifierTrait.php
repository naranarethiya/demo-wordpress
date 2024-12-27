<?php

namespace GoDaddy\WordPress\MWC\Core\Traits;

use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;

trait CanGetMerchantAccountIdentifierTrait
{
    /**
     * Gets the merchant account identifier.
     *
     * @return string
     * @throws PlatformRepositoryException
     */
    protected function getMerchantAccountIdentifier() : string
    {
        $platformRepository = PlatformRepositoryFactory::getNewInstance()->getPlatformRepository();

        if (! $identifier = $platformRepository->getVentureId() ?: $platformRepository->getChannelId()) {
            SentryException::getNewInstance('Merchant account identifier not found.');
        }

        return $identifier;
    }
}
