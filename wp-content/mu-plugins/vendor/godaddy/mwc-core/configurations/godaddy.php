<?php

return [

    /*
     *--------------------------------------------------------------------------
     * Information related to the GoDaddy platform
     *--------------------------------------------------------------------------
     */
    'platform' => [
        'repository' => GoDaddy\WordPress\MWC\Core\Repositories\ManagedWordPressPlatformRepository::class,
    ],
    /*
     *--------------------------------------------------------------------------
     * Information related to the Commerce store
     *--------------------------------------------------------------------------
     */
    'store' => [
        'shouldDetermineDefaultSiteId' => false,
        'channelId'                    => defined('GD_COMMERCE_CHANNEL_ID') ? GD_COMMERCE_CHANNEL_ID : '',
    ],
    /*
     *--------------------------------------------------------------------------
     * GoDaddy customer ID
     *--------------------------------------------------------------------------
     */
    'customerId' => defined('GD_CUSTOMER_ID') ? GD_CUSTOMER_ID : '',
];
