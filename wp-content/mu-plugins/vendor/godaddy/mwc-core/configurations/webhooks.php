<?php

return [
    /*
     * Webhook Endpoints
     *
     * Each endpoint should have a unique namespace (array key) along with supported HTTP methods, middleware (optional),
     * and a handler.
     *
     * Middleware should implement the `WebhookMiddlewareContract` interface.
     */
    'endpoints' => [
        'commerce' => [
            'methods'    => ['POST'],
            'middleware' => [
                GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Http\ValidCommerceWebhookMiddleware::class,
                GoDaddy\WordPress\MWC\Core\Webhooks\Middleware\ParseWebhookIdFromStandardWebhookMiddleware::class,
                GoDaddy\WordPress\MWC\Core\Webhooks\Middleware\ParseOccurredAtFromStandardWebhookMiddleware::class,
                GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Middleware\ParseResourceIdFromCommerceWebhookMiddleware::class,
            ],
            'handler' => GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Handlers\WebhookHandler::class, // @TODO this is just an example; to be implemented in a MWC-16900
        ],
    ],

    /*
     * Legacy Webhook Endpoints
     *
     * These endpoints use the `/wc-api/` infrastructure, and should no longer be used.
     */
    'legacy-endpoints' => [
        [
            'namespace'  => 'poynt',
            'eventClass' => GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\WebhookReceivedEvent::class,
        ],
        [
            'namespace'  => 'marketplaces',
            'eventClass' => GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\WebhookReceivedEvent::class,
        ],
    ],
];
