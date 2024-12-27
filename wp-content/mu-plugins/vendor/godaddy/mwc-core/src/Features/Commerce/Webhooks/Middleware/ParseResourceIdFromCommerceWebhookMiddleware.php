<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Middleware;

use Closure;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\IncomingRequest;
use GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects\IncomingWebhookRequest;
use GoDaddy\WordPress\MWC\Core\Webhooks\Middleware\Contracts\WebhookMiddlewareContract;

/**
 * Parses remote resource IDs from payloads using the Standard Webhook spec.
 * @link https://github.com/standard-webhooks/standard-webhooks/
 */
class ParseResourceIdFromCommerceWebhookMiddleware implements WebhookMiddlewareContract
{
    /**
     * Attempts to parse the remote resource ID from the request body, and sets it on the request object.
     *
     * @param IncomingRequest $request
     * @param Closure $next
     * @return IncomingRequest
     */
    public function handle(IncomingRequest $request, Closure $next) : IncomingRequest
    {
        if ($request instanceof IncomingWebhookRequest && $remoteResourceId = $this->getRemoteResourceIdFromRequest($request)) {
            $request->setRemoteResourceId($remoteResourceId);
        }

        return $next($request);
    }

    /**
     * Gets the remote resource ID from the request body.
     *
     * @param IncomingWebhookRequest $request
     * @return string|null
     */
    protected function getRemoteResourceIdFromRequest(IncomingWebhookRequest $request) : ?string
    {
        $decodedRequestBody = ArrayHelper::wrap(json_decode(TypeHelper::string($request->getBody(), ''), true));

        if (StringHelper::contains(ArrayHelper::getStringValueForKey($decodedRequestBody, 'type'), '.product.')) {
            return ArrayHelper::getStringValueForKey($decodedRequestBody, 'data.productId') ?: null;
        }

        return null;
    }
}
