<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Webhooks;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Services\Contracts\SubscriptionServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Services\SubscriptionService;

class WebhookServicesServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        SubscriptionServiceContract::class,
    ];

    public function register() : void
    {
        $this->getContainer()->singleton(SubscriptionServiceContract::class, SubscriptionService::class);
    }
}
