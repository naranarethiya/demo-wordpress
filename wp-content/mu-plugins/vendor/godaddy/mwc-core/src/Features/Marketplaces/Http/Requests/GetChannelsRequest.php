<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Http\Requests;

/**
 * API request to get a list of connected channels from GDM.
 */
class GetChannelsRequest extends GoDaddyMarketplacesRequest
{
    /** @var string request route */
    protected $route = 'events';

    /**
     * Builds the request body.
     *
     * @return array<string, mixed>
     */
    protected function buildBodyData() : array
    {
        return [
            'partner' => static::PARTNER,
            'event'   => [
                'event_name' => 'MERCHANT_CHANNELS_CONNECTED',
                'event_data' => [],
            ],
        ];
    }
}
