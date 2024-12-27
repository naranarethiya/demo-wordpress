<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers;

use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Events\AbstractWebhookReceivedEvent;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Webhooks\OrderWebhookPayload;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Services\OrderUpsertService;

/**
 * The Marketplaces order webhook subscriber.
 */
class OrderWebhookSubscriber extends AbstractWebhookSubscriber implements ComponentContract
{
    /** @var string */
    protected string $webhookType = 'order';

    /**
     * Maybe creates an order in WooCommerce from a new order placed through GDM.
     *
     * @param AbstractWebhookReceivedEvent $event
     * @return void
     * @throws AdapterException|SentryException
     */
    public function handlePayload(AbstractWebhookReceivedEvent $event) : void
    {
        /** @var OrderWebhookPayload|null $webhookPayload */
        $webhookPayload = $this->getWebhookPayload($event);

        if (! $webhookPayload) {
            return;
        }

        OrderUpsertService::getNewInstance($webhookPayload)->saveOrder();
    }

    /**
     * {@inheritDoc}
     */
    public function load() : void
    {
        // not implemented
    }
}
