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
        'dsn'     => null,
        'enabled' => true,
        /*
         * Sample rate params for sentry events.
         *
         * defaultRate: a float in the range of 0.0 to 1.0 (inclusive). Applied to all events by default.
         * overrides: associative array map of class-string => float
         *
         * {@see SentrySampleRateRepository}
         */
        'sampleRateParams' => [
            'defaultRate' => 0.2,
            'overrides'   => [
                // SomeException::class => 0.5, // example
            ],
        ],
    ],
];
