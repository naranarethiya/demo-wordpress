<?php

namespace GoDaddy\WordPress\MWC\Core\Webhooks\Middleware;

use Closure;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\IncomingRequest;
use GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects\IncomingWebhookRequest;
use GoDaddy\WordPress\MWC\Core\Webhooks\Exceptions\WebhookProcessingException;
use GoDaddy\WordPress\MWC\Core\Webhooks\Middleware\Contracts\WebhookMiddlewareContract;

/**
 * Parses webhook timestamp from payloads using the Standard Webhook spec.
 * @link https://github.com/standard-webhooks/standard-webhooks/
 */
class ParseOccurredAtFromStandardWebhookMiddleware implements WebhookMiddlewareContract
{
    /**
     * Attempts to parse the timestamp from the request body, and sets it on the request object.
     *
     * @param IncomingRequest $request
     * @param Closure $next
     * @return IncomingRequest
     * @throws Exception
     */
    public function handle(IncomingRequest $request, Closure $next) : IncomingRequest
    {
        if ($request instanceof IncomingWebhookRequest) {
            $request->setOccurredAt($this->getTimestampFromRequest($request));
        }

        return $next($request);
    }

    /**
     * Gets the timestamp from the request body.
     *
     * @param IncomingWebhookRequest $request
     * @return DateTimeImmutable
     * @throws Exception
     */
    protected function getTimestampFromRequest(IncomingWebhookRequest $request) : DateTimeImmutable
    {
        if ($timestamp = ArrayHelper::getStringValueForKey((array) json_decode((string) $request->getBody(), true), 'timestamp')) {
            return new DateTimeImmutable($timestamp, new DateTimeZone('UTC'));
        }

        throw new WebhookProcessingException('No timestamp found in request body.');
    }
}
