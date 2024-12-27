<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Configuration;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Configuration\Contracts\CommerceWebhooksRuntimeConfigurationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Handlers\Contracts\WebhookEventTypeHandlerContract;

/**
 * Runtime configuration class to determine which webhook event types to subscribe to, based on which Commerce
 * integrations are enabled and have read capabilities.
 */
class CommerceWebhooksRuntimeConfiguration implements CommerceWebhooksRuntimeConfigurationContract
{
    /**
     * {@inheritDoc}
     */
    public function getEnabledWebhookEventTypes() : array
    {
        if (! $integrationConfigs = TypeHelper::array(Configuration::get('features.commerce.integrations', []), [])) {
            return [];
        }

        $combinedEventTypes = [];

        foreach ($integrationConfigs as $integrationConfig) {
            $combinedEventTypes = array_merge($combinedEventTypes, $this->getIntegrationEnabledEventTypes(TypeHelper::array($integrationConfig, [])));
        }

        return TypeHelper::arrayOfClassStrings($combinedEventTypes, WebhookEventTypeHandlerContract::class, true);
    }

    /**
     * {@inheritDoc}
     */
    public function getEnabledWebhookEventTypeNames() : array
    {
        return array_keys($this->getEnabledWebhookEventTypes());
    }

    /**
     * Gets the enabled event types for a single integration.
     *
     * If the integration is disabled, then an empty array is returned, regardless of what's registered.
     *
     * @param array<string, mixed> $config the integration's configuration settings (see features.php)
     * @return array<string, string>
     */
    protected function getIntegrationEnabledEventTypes(array $config) : array
    {
        // Fastest check is just an early bail if no event types are registered.
        /** @var array<string, string> $eventTypes */
        $eventTypes = TypeHelper::arrayOfStrings(ArrayHelper::get($config, 'webhooks.eventTypes'));

        if (! $eventTypes) {
            return [];
        }

        if (! $className = TypeHelper::string(ArrayHelper::get($config, 'className'), '')) {
            return [];
        }

        if (! $this->integrationShouldLoad($className) || ! $this->integrationHasReadCapability($className)) {
            return [];
        }

        return $eventTypes;
    }

    /**
     * Determines whether the integration is loaded.
     *
     * @param string $className
     * @return bool
     */
    protected function integrationShouldLoad(string $className) : bool
    {
        return class_exists($className) && method_exists($className, 'shouldLoad') && $className::shouldLoad();
    }

    /**
     * Determines whether the integration has the "read" capability enabled.
     *
     * @param string $className
     * @return bool
     */
    protected function integrationHasReadCapability(string $className) : bool
    {
        return class_exists($className) && method_exists($className, 'hasCommerceCapability') && $className::hasCommerceCapability(Commerce::CAPABILITY_READ);
    }
}
