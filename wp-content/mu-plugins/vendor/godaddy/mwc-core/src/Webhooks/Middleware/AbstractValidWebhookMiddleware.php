<?php

namespace GoDaddy\WordPress\MWC\Core\Webhooks\Middleware;

use Closure;
use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\ValidationHelper;
use GoDaddy\WordPress\MWC\Common\Http\IncomingRequest;
use GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects\IncomingWebhookRequest;
use GoDaddy\WordPress\MWC\Core\Webhooks\Middleware\Contracts\WebhookMiddlewareContract;

abstract class AbstractValidWebhookMiddleware implements WebhookMiddlewareContract
{
    /**
     * Handles a webhook request.
     *
     * This ensures that the request is a valid webhook.
     *
     * @param IncomingWebhookRequest $request
     * @param Closure $next
     * @return IncomingWebhookRequest
     * @throws Exception
     */
    public function handle(IncomingRequest $request, Closure $next) : IncomingRequest
    {
        if (! $secret = $this->getWebhookSecret()) {
            throw new Exception('Unable to validate webhook. Webhook subscription secret is missing.', 500);
        }

        if (! ValidationHelper::isValidWebhook(TypeHelper::string($secret, ''), TypeHelper::string($request->getBody(), ''), $request->getHeaders())) {
            throw new Exception('Invalid webhook signature.', 422);
        }

        return $next($request);
    }

    /**
     * Gets the webhook secret.
     *
     * @return string|null
     */
    abstract protected function getWebhookSecret() : ?string;
}
