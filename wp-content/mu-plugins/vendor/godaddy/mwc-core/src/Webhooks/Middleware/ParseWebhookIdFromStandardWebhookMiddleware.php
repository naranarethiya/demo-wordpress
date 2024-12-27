<?php

namespace GoDaddy\WordPress\MWC\Core\Webhooks\Middleware;

use Closure;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\IncomingRequest;
use GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects\IncomingWebhookRequest;
use GoDaddy\WordPress\MWC\Core\Webhooks\Middleware\Contracts\WebhookMiddlewareContract;

/**
 * Parses webhook IDs from payloads using the Standard Webhook spec.
 * @link https://github.com/standard-webhooks/standard-webhooks/
 */
class ParseWebhookIdFromStandardWebhookMiddleware implements WebhookMiddlewareContract
{
    /**
     * Attempts to parse the webhook ID from the request headers, and sets it on the request object.
     *
     * @param IncomingWebhookRequest $request
     * @param Closure $next
     * @return IncomingWebhookRequest
     */
    public function handle(IncomingRequest $request, Closure $next) : IncomingRequest
    {
        if ($request instanceof IncomingWebhookRequest && $webhookId = $this->getWebhookIdFromRequest($request)) {
            $request->setWebhookId($webhookId);
        }

        return $next($request);
    }

    /**
     * Gets the webhook ID from the request headers.
     *
     * @param IncomingWebhookRequest $request
     * @return string
     */
    protected function getWebhookIdFromRequest(IncomingWebhookRequest $request) : string
    {
        return ArrayHelper::getStringValueForKey($request->getHeaders(), 'HTTP_WEBHOOK_ID');
    }
}
