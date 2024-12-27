<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Http\Requests;

use GoDaddy\WordPress\MWC\Common\Http\GoDaddyRequest;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Http\Traits\IsCommerceRequestTrait;

/**
 * Abstract Request class.
 */
abstract class AbstractRequest extends GoDaddyRequest
{
    use IsCommerceRequestTrait;
}
