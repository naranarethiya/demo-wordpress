<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Webhooks;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Configuration\CommerceWebhooksRuntimeConfiguration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Configuration\Contracts\CommerceWebhooksRuntimeConfigurationContract;

class CommerceWebhooksRuntimeConfigurationProvider extends AbstractServiceProvider
{
    protected array $provides = [CommerceWebhooksRuntimeConfigurationContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->singleton(CommerceWebhooksRuntimeConfigurationContract::class, CommerceWebhooksRuntimeConfiguration::class);
    }
}
