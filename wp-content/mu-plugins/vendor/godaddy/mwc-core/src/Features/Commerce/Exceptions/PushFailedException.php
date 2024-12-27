<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\SyncExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\IsCommerceExceptionTrait;

abstract class PushFailedException extends BaseException implements SyncExceptionContract
{
    use IsCommerceExceptionTrait;

    /** @var string */
    protected string $errorCode = 'COMMERCE_PUSH_FAILED_EXCEPTION';
}
