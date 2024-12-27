<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Exceptions;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;

/**
 * Exception thrown when we expect a summary to exist upstream but we're unable to find it.
 */
class SummaryNotFoundException extends CommerceException
{
    protected string $errorCode = 'COMMERCE_INVENTORY_SUMMARY_NOT_FOUND_EXCEPTION';
}
