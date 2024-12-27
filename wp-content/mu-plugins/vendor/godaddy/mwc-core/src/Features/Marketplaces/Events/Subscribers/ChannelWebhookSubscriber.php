<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Events\AbstractWebhookReceivedEvent;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Cache\ConnectedChannelsCache;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\ChannelConnectedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\ChannelDisabledEvent;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Webhooks\ChannelWebhookPayload;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Repositories\ChannelRepository;

/**
 * The Marketplaces channel webhook subscriber.
 */
class ChannelWebhookSubscriber extends AbstractWebhookSubscriber implements ComponentContract
{
    /** @var string */
    protected string $webhookType = 'channel';

    /**
     * Handles the webhook payload.
     *
     * @param AbstractWebhookReceivedEvent $event
     * @throws SentryException|BaseException|Exception
     */
    public function handlePayload(AbstractWebhookReceivedEvent $event) : void
    {
        /** @var ChannelWebhookPayload|null $webhookPayload */
        $webhookPayload = $this->getWebhookPayload($event);

        if (! $webhookPayload) {
            return;
        }

        $this->maybeBroadcastChannelEvent($webhookPayload);

        // clear cached channels
        ConnectedChannelsCache::getInstance()->clear();

        // trigger a new API request
        ChannelRepository::getConnected();
    }

    /**
     * Maybe broadcasts a channel event.
     *
     * @param ChannelWebhookPayload $webhookPayload
     * @return void
     */
    protected function maybeBroadcastChannelEvent(ChannelWebhookPayload $webhookPayload) : void
    {
        $channel = $webhookPayload->getChannel();
        $eventType = $webhookPayload->getEventType();

        if ($channel && $eventType === 'channel_created') {
            Events::broadcast(new ChannelConnectedEvent($channel));
        }

        if ($channel && $eventType === 'channel_destroyed') {
            Events::broadcast(new ChannelDisabledEvent($channel));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function load() : void
    {
        // not implemented
    }
}
