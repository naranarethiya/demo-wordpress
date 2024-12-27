<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Adapters\Webhooks;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Adapters\OrderAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Webhooks\OrderWebhookPayload;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\InvalidProductException;

/**
 * Adapts data from a GDM order webhook payload to a native OrderWebhookPayload object.
 */
class OrderWebhookPayloadAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var array<string, mixed> Order data from the webhook payload */
    protected $source;

    /**
     * OrderWebhookPayloadAdapter constructor.
     *
     * @param array<string, mixed> $decodedWebhookPayload Decoded data from the webhook payload.
     */
    public function __construct(array $decodedWebhookPayload)
    {
        $this->source = $decodedWebhookPayload;
    }

    /**
     * Converts the decoded payload into a OrderWebhookPayload object.
     *
     * @return OrderWebhookPayload
     * @throws AdapterException|InvalidProductException
     */
    public function convertFromSource() : OrderWebhookPayload
    {
        return (new OrderWebhookPayload())
            ->setEventType(TypeHelper::string(ArrayHelper::get($this->source, 'event_type'), ''))
            ->setIsExpectedEvent($this->isOrderEvent())
            ->setOrder(OrderAdapter::getNewInstance(ArrayHelper::get($this->source, 'payload', []))->convertFromSource());
    }

    /**
     * Determines if the webhook received is for an order event.
     *
     * @return bool
     */
    protected function isOrderEvent() : bool
    {
        return 'webhook_order' === ArrayHelper::get($this->source, 'event_type');
    }

    /**
     * {@inheritDoc}
     */
    public function convertToSource()
    {
        // Not implemented.
        return [];
    }
}
