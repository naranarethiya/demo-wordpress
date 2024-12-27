<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Handlers;

use GoDaddy\WordPress\MWC\Common\Container\ContainerFactory;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\ContainerException;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\EntryNotFoundException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Configuration\Contracts\CommerceWebhooksRuntimeConfigurationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Handlers\Contracts\WebhookEventTypeHandlerContract;
use GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects\Webhook;
use GoDaddy\WordPress\MWC\Core\Webhooks\Exceptions\WebhookProcessingException;
use GoDaddy\WordPress\MWC\Core\Webhooks\Handlers\AbstractWebhookHandler;
use GoDaddy\WordPress\MWC\Core\Webhooks\Repositories\WebhooksRepository;

/**
 * Handles all incoming Commerce webhooks. At this point it could be any Commerce event type.
 */
class WebhookHandler extends AbstractWebhookHandler
{
    protected CommerceWebhooksRuntimeConfigurationContract $runtimeConfiguration;

    public function __construct(WebhooksRepository $webhooksRepository, CommerceWebhooksRuntimeConfigurationContract $runtimeConfiguration)
    {
        $this->runtimeConfiguration = $runtimeConfiguration;

        parent::__construct($webhooksRepository);
    }

    /**
     * Handles an incoming Commerce webhook by routing it to an event type-specific handler for processing.
     *
     * @throws WebhookProcessingException|ContainerException|EntryNotFoundException
     */
    public function handle(Webhook $webhook) : void
    {
        $decodedPayload = $this->getDecodedPayload($webhook);
        $eventTypeHandler = $this->getEventTypeHandler($this->getEventTypeFromPayload($decodedPayload));
        $eventTypeHandler->handle($webhook);
    }

    /**
     * Decodes the payload.
     *
     * @param Webhook $webhook
     * @return array<string, mixed>
     * @throws WebhookProcessingException
     */
    protected function getDecodedPayload(Webhook $webhook) : array
    {
        $decodedPayload = json_decode($webhook->payload, true);

        if (! is_array($decodedPayload)) {
            throw new WebhookProcessingException('Failed to decode webhook payload.');
        }

        return $decodedPayload;
    }

    /**
     * Gets the event type from the payload object.
     *
     * @param array<string, mixed> $payload
     * @return string
     * @throws WebhookProcessingException
     */
    protected function getEventTypeFromPayload(array $payload) : string
    {
        $eventType = ArrayHelper::getStringValueForKey($payload, 'type', '');
        if ($eventType) {
            return $eventType;
        }

        throw new WebhookProcessingException('Failed to parse event type from payload.');
    }

    /**
     * Gets the handler for the provided event type.
     *
     * @param string $eventType
     * @return WebhookEventTypeHandlerContract
     * @throws WebhookProcessingException|ContainerException|EntryNotFoundException
     */
    protected function getEventTypeHandler(string $eventType) : WebhookEventTypeHandlerContract
    {
        $handlerClassName = ArrayHelper::getStringValueForKey($this->runtimeConfiguration->getEnabledWebhookEventTypes(), $eventType);
        if (! $handlerClassName) {
            throw new WebhookProcessingException("Event type {$eventType} is not registered.");
        }

        if (! class_exists($handlerClassName)) {
            throw new WebhookProcessingException("Class {$handlerClassName} does not exist for event type {$eventType}.");
        }

        return $this->getHandlerClassInstance($handlerClassName);
    }

    /**
     * Gets an instance of the provided handler class name.
     *
     * @param string $className
     * @return WebhookEventTypeHandlerContract
     * @throws WebhookProcessingException|ContainerException|EntryNotFoundException
     */
    protected function getHandlerClassInstance(string $className) : WebhookEventTypeHandlerContract
    {
        $handler = ContainerFactory::getInstance()->getSharedContainer()->get($className);

        if ($handler instanceof WebhookEventTypeHandlerContract) {
            return $handler;
        }

        throw new WebhookProcessingException("The {$className} class must implement the WebhookEventTypeHandlerContract interface.");
    }
}
