<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\IsCommerceExceptionTrait;

class CommerceException extends BaseException implements CommerceExceptionContract
{
    use IsCommerceExceptionTrait;

    protected string $errorCode = 'COMMERCE_EXCEPTION';
}
