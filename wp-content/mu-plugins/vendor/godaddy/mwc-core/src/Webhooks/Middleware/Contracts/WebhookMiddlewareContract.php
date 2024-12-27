<?php

namespace GoDaddy\WordPress\MWC\Core\Webhooks\Middleware\Contracts;

use Closure;
use Exception;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\MiddlewareContract;
use GoDaddy\WordPress\MWC\Common\Http\IncomingRequest;
use GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects\IncomingWebhookRequest;

interface WebhookMiddlewareContract extends MiddlewareContract
{
    /**
     * Handles an incoming webhook request.
     *
     * @param IncomingWebhookRequest $request
     * @param Closure $next
     * @return IncomingWebhookRequest
     * @throws Exception
     */
    public function handle(IncomingRequest $request, Closure $next) : IncomingRequest;
}
