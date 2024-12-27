<?php

namespace GoDaddy\WordPress\MWC\Common\Http\Contracts;

use Closure;
use GoDaddy\WordPress\MWC\Common\Http\IncomingRequest;

interface MiddlewareContract
{
    /**
     * Handles an incoming request.
     *
     * @param IncomingRequest $request
     * @param Closure $next
     * @return IncomingRequest
     */
    public function handle(IncomingRequest $request, Closure $next) : IncomingRequest;
}
