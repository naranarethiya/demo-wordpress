<?php

use GoDaddy\WordPress\MWC\Common\HostingPlans\Enums\HostingPlanNamesEnum;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Adapters\Webhooks\ChannelWebhookPayloadAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Adapters\Webhooks\GoogleAdsTrackingWebhookPayloadAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Adapters\Webhooks\GoogleVerificationWebhookPayloadAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Adapters\Webhooks\ListingWebhookPayloadAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Adapters\Webhooks\MerchantProvisionedViaChatterboxWebhookPayloadAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Adapters\Webhooks\OrderWebhookPayloadAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Channel;

return [
    /*
     *--------------------------------------------------------------------------
     * GoDaddy Marketplaces API
     *--------------------------------------------------------------------------
     */
    'api' => [
        'url' => defined('MWC_GDM_API_URL') ? MWC_GDM_API_URL : 'https://marketplaces.godaddy.com/api',
    ],

    /*
     *--------------------------------------------------------------------------
     * Marketplaces orders quota for plans
     *--------------------------------------------------------------------------
     */
    'plan_limits' => [
        HostingPlanNamesEnum::Essentials         => 1000,
        HostingPlanNamesEnum::EssentialsCA       => 1000,
        HostingPlanNamesEnum::EssentialsWorldpay => 1000,
        HostingPlanNamesEnum::Flex               => 1000,
        HostingPlanNamesEnum::FlexCA             => 1000,
        HostingPlanNamesEnum::FlexWorldpay       => 1000,
        HostingPlanNamesEnum::Expand             => 2500,
        HostingPlanNamesEnum::ExpandCA           => 2500,
        HostingPlanNamesEnum::ExpandWorldpay     => 2500,
        HostingPlanNamesEnum::Premier            => 5000,
    ],

    /*
     *--------------------------------------------------------------------------
     * Available Sales Channels
     *--------------------------------------------------------------------------
     */
    'channels' => [
        'types' => defined('MWC_GDM_CHANNEL_TYPES')
            ? (array) MWC_GDM_CHANNEL_TYPES
            : [
                Channel::TYPE_AMAZON   => 'Amazon',
                Channel::TYPE_EBAY     => 'eBay',
                Channel::TYPE_FACEBOOK => 'Facebook',
                Channel::TYPE_WALMART  => 'Walmart',
                Channel::TYPE_ETSY     => 'Etsy',
                Channel::TYPE_GOOGLE   => 'Google',
            ],

        /* Google channel specific settings */
        'google' => [
            'productIdRequestRetryIntervalMinutes' => 5,
            'productIdRequestMaxAttempts'          => 4,
        ],
    ],

    /*
     *--------------------------------------------------------------------------
     * Webhooks
     *--------------------------------------------------------------------------
     */
    'webhooks' => [
        'adapters' => [
            'chatterboxProvisioned' => MerchantProvisionedViaChatterboxWebhookPayloadAdapter::class,
            'googleTracking'        => GoogleAdsTrackingWebhookPayloadAdapter::class,
            'googleVerification'    => GoogleVerificationWebhookPayloadAdapter::class,
            'listing'               => ListingWebhookPayloadAdapter::class,
            'channel'               => ChannelWebhookPayloadAdapter::class,
            'order'                 => OrderWebhookPayloadAdapter::class,
        ],
    ],

    /*
     *--------------------------------------------------------------------------
     * GoDaddy Marketplaces website
     *--------------------------------------------------------------------------
     */
    'website' => [
        'url'              => 'https://marketplaces.godaddy.com',
        'salesChannelsUrl' => 'https://spa.commerce.godaddy.com',
        'commerceHubUrl'   => 'https://hub.commerce.godaddy.com',
    ],
];
