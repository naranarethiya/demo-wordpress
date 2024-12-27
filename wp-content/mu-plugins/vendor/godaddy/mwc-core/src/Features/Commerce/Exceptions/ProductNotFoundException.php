<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions;

/**
 * Exception thrown when we try to access a product by its remote ID and the Catalog Service responds
 * with a Not Found error.
 *
 * Note that this exception can trigger logic that permanently deletes local records. As a result,
 * this exception must not be thrown when a product doesn't show up in the results of a search
 * request. It should only be used when a direct request for the product results in a Not Found error.
 */
class ProductNotFoundException extends CommerceException
{
    protected string $errorCode = 'COMMERCE_PRODUCT_NOT_FOUND_EXCEPTION';
}
