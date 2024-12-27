<?php

use GoDaddy\WordPress\MWC\Core\Analytics\Providers\Contracts\AnalyticsProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Google\GoogleMarketplacesAnalyticsProvider;

return [
    'enabled' => true,
    /*
     *-------------------------------------------------------------------------------
     * A list of analytics providers that implement {@see AnalyticsProviderContract}.
     *-------------------------------------------------------------------------------
     */
    'providers' => [
        GoogleMarketplacesAnalyticsProvider::class,
    ],
    /*
     *-------------------------------------------------------------------------------
     * Configurations used by individual providers.
     *-------------------------------------------------------------------------------
     */
    'google' => [
        'developerId' => 'dTZmYj',
    ],
];
