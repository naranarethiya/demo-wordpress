<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\IsCommerceExceptionTrait;

/**
 * Exception thrown when attempting to create a new product in the Platform, but the product is not creatable.
 */
class ProductNotCreatableException extends BaseException implements CommerceExceptionContract
{
    use IsCommerceExceptionTrait;

    /** @var string */
    protected string $errorCode = 'COMMERCE_PRODUCT_NOT_CREATABLE_EXCEPTION';
}
