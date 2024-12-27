<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Webhooks;

use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Channel;

/**
 * Object representing all the information we expect to receive from a channel webhook payload.
 */
class ChannelWebhookPayload extends AbstractWebhookPayload
{
    /** @var Channel|null */
    protected $channel;

    /**
     * Gets the channel.
     *
     * @return Channel
     */
    public function getChannel() : ?Channel
    {
        return $this->channel;
    }

    /**
     * Sets the channel.
     *
     * @param Channel|null $value
     * @return $this
     */
    public function setChannel(?Channel $value) : ChannelWebhookPayload
    {
        $this->channel = $value;

        return $this;
    }
}
