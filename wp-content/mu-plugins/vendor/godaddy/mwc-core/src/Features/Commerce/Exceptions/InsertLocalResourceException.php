<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractInsertLocalResourceService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\IsCommerceExceptionTrait;

/**
 * Exception thrown when errors occur during local resource insertion {@see AbstractInsertLocalResourceService::insert()}.
 */
class InsertLocalResourceException extends BaseException implements CommerceExceptionContract
{
    protected string $errorCode = 'INSERT_LOCAL_RESOURCE_EXCEPTION';

    use IsCommerceExceptionTrait;
}
