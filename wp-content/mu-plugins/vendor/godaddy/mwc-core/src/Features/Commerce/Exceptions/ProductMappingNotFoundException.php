<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceObjectMappingNotFoundExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\IsCommerceExceptionTrait;

/**
 * Exception for when a Commerce product mapping is not found.
 */
class ProductMappingNotFoundException extends BaseException implements CommerceObjectMappingNotFoundExceptionContract
{
    use IsCommerceExceptionTrait;

    /** @var string */
    protected string $errorCode = 'COMMERCE_PRODUCT_MAPPING_NOT_FOUND_EXCEPTION';
}
