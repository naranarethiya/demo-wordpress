<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceObjectMappingNotFoundExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\IsCommerceExceptionTrait;

/**
 * Exception that's thrown when a Commerce category mapping is not found.
 */
class CategoryMappingNotFoundException extends BaseException implements CommerceObjectMappingNotFoundExceptionContract
{
    use IsCommerceExceptionTrait;

    protected string $errorCode = 'COMMERCE_CATEGORY_MAPPING_NOT_FOUND_EXCEPTION';
}
