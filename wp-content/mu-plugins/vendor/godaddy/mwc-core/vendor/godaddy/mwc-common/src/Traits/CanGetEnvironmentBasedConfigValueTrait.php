<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformEnvironment;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;

/**
 * Allows classes to retrieve configuration values tailored to the current environment.
 */
trait CanGetEnvironmentBasedConfigValueTrait
{
    /**
     * Gets a configuration value for the current environment.
     *
     * @param string $key config key without the `.dev` or `.prod` suffix
     * @return mixed|null
     */
    protected function getEnvironmentConfigValue(string $key)
    {
        // appends either `.dev` or `.prod` to the end of the supplied key
        $keyWithEnvironmentSuffix = sprintf('%s.%s', $key, $this->getEnvironmentConfigKeySuffix());

        return Configuration::get($keyWithEnvironmentSuffix);
    }

    /**
     * Gets the correct suffix to use on a configuration key for the current environment.
     *
     * @return string Returns `dev` in test and local environments; returns `prod` otherwise.
     */
    protected function getEnvironmentConfigKeySuffix() : string
    {
        $environment = ManagedWooCommerceRepository::getEnvironment();

        return in_array($environment, [PlatformEnvironment::TEST, PlatformEnvironment::LOCAL], true)
            ? 'dev'
            : 'prod';
    }
}
