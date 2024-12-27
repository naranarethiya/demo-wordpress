<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Exceptions;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;

/**
 * Exception thrown when we expect a category to exist upstream but we're unable to find it.
 */
class CategoryNotFoundException extends CommerceException
{
    protected string $errorCode = 'COMMERCE_CATEGORY_NOT_FOUND_EXCEPTION';
}
