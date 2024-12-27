<?php

return [
    /*
     *--------------------------------------------------------------------------
     * Sentry Settings
     *--------------------------------------------------------------------------
     *
     * The following setting are related to Sentry for error reporting across
     * the broader platform.
     *
     * See https://sentry.io/
     *
     */
    'sentry' => [
        /* Override mwc-common declared value */
        'dsn' => 'https://a837a598f8134b31b95b796e34e4fa5d@o13756.ingest.sentry.io/5659275',
        /*
         * Sample rate params for sentry events.
         *
         * defaultRate: a float in the range of 0.0 to 1.0 (inclusive). Applied to all events by default.
         * overrides: associative array map of class-string => float|int
         *
         * {@see SentrySampleRateRepository}
         */
        'sampleRateParams' => [
            'overrides' => [
                GoDaddy\WordPress\MWC\Core\Auth\Sso\WordPress\Exceptions\SsoFailedException::class                                     => 1,
                GoDaddy\WordPress\MWC\Shipping\Exceptions\ShippingException::class                                                     => 1,
                GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Exceptions\WebhookSubscriptionOperationForbiddenException::class => 1,
            ],
        ],
    ],
    'logging' => [
        'woocommerce' => [
            'retentionDays' => [
                'default'  => 7,
                'override' => defined('MWC_WC_LOG_RETENTION_DAYS') ? MWC_WC_LOG_RETENTION_DAYS : null,
            ],
        ],
    ],
];
