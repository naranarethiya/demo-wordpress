<?php

namespace GoDaddy\WordPress\MWC\Common\Platforms;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Platforms\Contracts\PlatformRepositoryContract;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * Factory responsible for retrieving a concrete PlatformRepositoryContract for the current site's platform.
 */
class PlatformRepositoryFactory
{
    use CanGetNewInstanceTrait;

    /**
     * Retrieves the platform repository configured for the current platform environment.
     *
     * @return PlatformRepositoryContract
     * @throws PlatformRepositoryException
     */
    public function getPlatformRepository() : PlatformRepositoryContract
    {
        $platformRepository = Configuration::get('godaddy.platform.repository', '');

        $this->validatePlatformRepository($platformRepository);

        return new $platformRepository();
    }

    /**
     * Checks that the supplied class name is a valid PlatformRepositoryContract.
     *
     * @param string $className
     * @return void
     * @throws PlatformRepositoryException
     */
    public function validatePlatformRepository(string $className) : void
    {
        if (! class_exists($className)) {
            throw new PlatformRepositoryException(sprintf('The platform repository class "%s" does not exist.', $className));
        }

        if (! is_a($className, PlatformRepositoryContract::class, true)) {
            throw new PlatformRepositoryException(sprintf('The platform repository class "%s" does not implement PlatformRepositoryContract.', $className));
        }
    }
}
