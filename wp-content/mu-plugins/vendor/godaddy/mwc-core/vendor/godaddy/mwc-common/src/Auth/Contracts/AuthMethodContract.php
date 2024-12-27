<?php

namespace GoDaddy\WordPress\MWC\Common\Auth\Contracts;

use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;

/**
 * Contract to prepare request's authorization method.
 */
interface AuthMethodContract
{
    public function prepare(RequestContract $request) : RequestContract;
}
