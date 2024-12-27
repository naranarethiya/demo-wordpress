<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Platforms\Contracts\PlatformEnvironmentContract;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformEnvironment;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;

/**
 * Trait that forcibly enables the integration in test environments.
 */
trait IntegrationEnabledOnTestTrait
{
    /**
     * Gets a configuration value for this feature.
     *
     * Force-enables the integration on platform test environments.
     *
     * @param string $key dot notated array key for the feature sub-configuration
     * @param mixed $default default value to return
     * @return mixed
     */
    public static function getConfiguration(string $key, $default = null)
    {
        if ('enabled' === $key && static::isTestEnvironment()) {
            return true;
        }

        return Configuration::get(sprintf('features.%s.%s', static::getName(), $key), $default);
    }

    /**
     * Gets the platform environment.
     *
     * @return PlatformEnvironmentContract
     * @throws PlatformRepositoryException
     */
    protected static function getPlatformEnvironment() : PlatformEnvironmentContract
    {
        return PlatformRepositoryFactory::getNewInstance()
            ->getPlatformRepository()
            ->getPlatformEnvironment();
    }

    /**
     * Determines whether this is the test environment.
     *
     * @return bool
     */
    protected static function isTestEnvironment() : bool
    {
        try {
            return PlatformEnvironment::LOCAL === static::getPlatformEnvironment()->getEnvironment();
        } catch (PlatformRepositoryException $exception) {
            return false;
        }
    }
}
