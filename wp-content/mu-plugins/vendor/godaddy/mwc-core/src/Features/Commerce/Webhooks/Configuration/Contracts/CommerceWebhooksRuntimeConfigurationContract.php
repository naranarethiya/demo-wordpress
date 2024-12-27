<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Configuration\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Handlers\Contracts\WebhookEventTypeHandlerContract;

interface CommerceWebhooksRuntimeConfigurationContract
{
    /**
     * Get the webhook event types for each enabled integration.
     *
     * Ex. If the catalog integration is disabled, it should not return any event types
     * configured for that integration.
     *
     * @return array<string, class-string<WebhookEventTypeHandlerContract>> The enabled webhook event types. Key is the
     *                                                                      event type name, value is the associated
     *                                                                      handler calss.
     */
    public function getEnabledWebhookEventTypes() : array;

    /**
     * Gets the names of the webhook event types for each enabled integration.
     *
     * This is the same as {@see static::getEnabledWebhookEventTypes()}, but instead of returning an associative array
     * it just returns the array keys (event type names, without the handlers).
     *
     * @return string[]
     */
    public function getEnabledWebhookEventTypeNames() : array;
}
