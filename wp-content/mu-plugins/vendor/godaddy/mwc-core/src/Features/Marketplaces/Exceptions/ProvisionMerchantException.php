<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Http\Requests\ProvisionMerchantRequest;

/**
 * Exception to be thrown when {@see ProvisionMerchantRequest} fails.
 */
class ProvisionMerchantException extends SentryException
{
}
