<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\IsCommerceExceptionTrait;

/**
 * Exception thrown when a variant's "parent ID" is missing a remote ID.
 */
class MissingProductRemoteIdForParentException extends BaseException implements CommerceExceptionContract
{
    use IsCommerceExceptionTrait;

    protected string $errorCode = 'COMMERCE_MISSING_PARENT_PRODUCT_REMOTE_ID_EXCEPTION';
}
