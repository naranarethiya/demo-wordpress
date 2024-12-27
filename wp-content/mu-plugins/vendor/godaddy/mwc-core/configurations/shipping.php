<?php

use GoDaddy\WordPress\MWC\Core\Features\Shipping\Providers\ShipEngine\ShipEngineProvider;
use GoDaddy\WordPress\MWC\Core\Features\Shipping\Services\ShipmentTrackingService;
use GoDaddy\WordPress\MWC\Dashboard\Shipping\DataStores\ShipmentTracking\OrderFulfillmentDataStore;

return [
    'localPickupInstructions' => [
        'active' => get_option('mwc_local_pickup_instructions_active', 'yes') === 'yes',
    ],
    'orderFulfillment' => [
        'dataStore' => OrderFulfillmentDataStore::class,
    ],
    'labels' => [
        'trackingService' => ShipmentTrackingService::class,
        'provider'        => 'shipengine',
    ],
    'providers' => [
        'shipengine'     => ShipEngineProvider::class,
        'australia-post' => GoDaddy\WordPress\MWC\Shipping\Providers\AustraliaPost\AustraliaPostProvider::class,
        'canada-post'    => GoDaddy\WordPress\MWC\Shipping\Providers\CanadaPost\CanadaPostProvider::class,
        'dhl'            => GoDaddy\WordPress\MWC\Shipping\Providers\DHL\DHLProvider::class,
        'fedex'          => GoDaddy\WordPress\MWC\Shipping\Providers\FedEx\FedExProvider::class,
        'ontrac'         => GoDaddy\WordPress\MWC\Shipping\Providers\OnTrac\OnTracProvider::class,
        'ups'            => GoDaddy\WordPress\MWC\Shipping\Providers\UPS\UPSProvider::class,
        'usps'           => GoDaddy\WordPress\MWC\Shipping\Providers\USPS\USPSProvider::class,
    ],
    'shipengine' => [
        'api' => [
            'url' => [
                'prod' => defined('MWC_SHIPENGINE_API_URL') ? MWC_SHIPENGINE_API_URL : (defined('MWC_EXTENSIONS_API_URL') ? MWC_EXTENSIONS_API_URL : 'https://api.mwc.secureserver.net/v1'),
                'dev'  => defined('MWC_SHIPENGINE_API_URL') ? MWC_SHIPENGINE_API_URL : (defined('MWC_EXTENSIONS_API_URL') ? MWC_EXTENSIONS_API_URL : 'https://api-test.mwc.secureserver.net/v1'),
            ],
        ],
        'account' => [
            'maxCompanyNameLength' => 50,
        ],
    ],
];
