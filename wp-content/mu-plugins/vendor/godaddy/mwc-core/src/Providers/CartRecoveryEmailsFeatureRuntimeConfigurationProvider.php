<?php

namespace GoDaddy\WordPress\MWC\Core\Providers;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Configuration\CartRecoveryEmailsFeatureRuntimeConfiguration;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Configuration\Contracts\CartRecoveryEmailsFeatureRuntimeConfigurationContract;

class CartRecoveryEmailsFeatureRuntimeConfigurationProvider extends AbstractServiceProvider
{
    protected array $provides = [CartRecoveryEmailsFeatureRuntimeConfigurationContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->singleton(CartRecoveryEmailsFeatureRuntimeConfigurationContract::class, CartRecoveryEmailsFeatureRuntimeConfiguration::class);
    }
}
