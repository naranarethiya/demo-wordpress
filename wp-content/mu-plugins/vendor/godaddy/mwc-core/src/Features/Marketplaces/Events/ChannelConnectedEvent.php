<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events;

use GoDaddy\WordPress\MWC\Common\Events\ModelEvent;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Channel;

/**
 * Event for when a sales channel is connected.
 */
class ChannelConnectedEvent extends ModelEvent
{
    /**
     * Event constructor.
     *
     * @param Channel $channel
     */
    public function __construct(Channel $channel)
    {
        parent::__construct($channel, 'marketplaces_channel', 'connected');
    }
}
