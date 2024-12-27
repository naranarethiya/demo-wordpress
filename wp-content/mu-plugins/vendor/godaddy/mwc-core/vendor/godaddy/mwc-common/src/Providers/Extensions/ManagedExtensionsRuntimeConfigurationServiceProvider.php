<?php

namespace GoDaddy\WordPress\MWC\Common\Providers\Extensions;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Common\Extensions\Configuration\Contracts\ManagedExtensionsRuntimeConfigurationContract;
use GoDaddy\WordPress\MWC\Common\Extensions\Configuration\ManagedExtensionsRuntimeConfiguration;

class ManagedExtensionsRuntimeConfigurationServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [ManagedExtensionsRuntimeConfigurationContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->bind(
            ManagedExtensionsRuntimeConfigurationContract::class,
            ManagedExtensionsRuntimeConfiguration::class
        );
    }
}
