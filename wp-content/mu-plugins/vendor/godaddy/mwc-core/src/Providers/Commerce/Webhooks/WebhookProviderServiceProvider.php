<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Webhooks;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\Contracts\WebhookProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\GoDaddy\WebhookProvider;

class WebhookProviderServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        WebhookProviderContract::class,
    ];

    public function register() : void
    {
        $this->getContainer()->singleton(WebhookProviderContract::class, WebhookProvider::class);
    }
}
