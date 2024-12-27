<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Adapters\Webhooks;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Channel;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Webhooks\ChannelWebhookPayload;

/**
 * Adapts data from a GDM channel webhook payload to a native ListingWebhookPayload object.
 */
class ChannelWebhookPayloadAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var array<string, mixed> Channel data from the webhook payload */
    protected $source;

    /**
     * ChannelWebhookPayloadAdapter constructor.
     *
     * @param array<string, mixed> $decodedWebhookPayload Decoded data from the webhook payload.
     */
    public function __construct(array $decodedWebhookPayload)
    {
        $this->source = $decodedWebhookPayload;
    }

    /**
     * Converts the decoded payload into a ChannelWebhookPayload object.
     *
     * @return ChannelWebhookPayload
     */
    public function convertFromSource() : ChannelWebhookPayload
    {
        return (new ChannelWebhookPayload())
            // @note it's expected that this key `event` differs from other payloads, which use `event_type`
            ->setEventType(TypeHelper::string(ArrayHelper::get($this->source, 'event'), ''))
            ->setIsExpectedEvent($this->isChannelEvent())
            ->setChannel($this->adaptChannel());
    }

    /**
     * Determines if the webhook received is for a channel event.
     *
     * @return bool
     */
    protected function isChannelEvent() : bool
    {
        return ArrayHelper::contains(['channel_created', 'channel_updated', 'channel_destroyed'], ArrayHelper::get($this->source, 'event'));
    }

    /**
     * Creates a Channel object from the webhook payload.
     *
     * @return Channel
     */
    protected function adaptChannel() : Channel
    {
        return (new Channel())
            ->setUuid((string) ArrayHelper::get($this->source, 'channel_uuid', ''))
            ->setType((string) ArrayHelper::get($this->source, 'provider', ''));
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
