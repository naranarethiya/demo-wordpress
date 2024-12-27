<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Http\Requests;

use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Handlers\MerchantAccountLevelDataHandler;

/**
 * API request to update merchant account data/settings.
 */
class UpdateMerchantAccountLevelDataRequest extends GoDaddyMarketplacesRequest
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
                'event_name' => 'TAX_SHIPMENT_SETTINGS',
                'event_data' => MerchantAccountLevelDataHandler::getStoreData(),
            ],
        ];
    }
}
