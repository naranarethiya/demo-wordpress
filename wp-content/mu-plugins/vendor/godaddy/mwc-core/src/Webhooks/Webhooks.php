<?php

namespace GoDaddy\WordPress\MWC\Core\Webhooks;

use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentClassesNotDefinedException;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentLoadFailedException;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Core\Webhooks\Actions\CreateWebhooksTableAction;
use GoDaddy\WordPress\MWC\Core\Webhooks\Interceptors\ProcessWebhookJobInterceptor;
use GoDaddy\WordPress\MWC\Core\Webhooks\Interceptors\WebhookRequestInterceptor;

/**
 * Webhooks Component.
 *
 * Facilitates receiving webhooks from external sources and routing them to the correct handler.
 * See config: configurations/webhooks.php
 */
class Webhooks implements ConditionalComponentContract
{
    use HasComponentsTrait;

    /** @var class-string<ComponentContract>[] */
    protected array $componentClasses = [
        CreateWebhooksTableAction::class,
        ProcessWebhookJobInterceptor::class,
        WebhookRequestInterceptor::class,
    ];

    /**
     * Loads the components.
     *
     * @throws ComponentClassesNotDefinedException|ComponentLoadFailedException
     */
    public function load() : void
    {
        $this->loadComponents();
    }

    /** {@inheritDoc} */
    public static function shouldLoad() : bool
    {
        return true;
    }
}
