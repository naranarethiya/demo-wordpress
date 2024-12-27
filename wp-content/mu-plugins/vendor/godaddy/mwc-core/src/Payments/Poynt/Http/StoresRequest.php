<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * Stores for businesses request.
 */
class StoresRequest extends AbstractBusinessRequest
{
    use CanGetNewInstanceTrait;

    /** @var string request route */
    protected $route = 'stores';
}
