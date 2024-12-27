<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\IsCommerceExceptionTrait;

class MissingCategoryLocalIdException extends BaseException
{
    use IsCommerceExceptionTrait;

    protected string $errorCode = 'MISSING_CATEGORY_LOCAL_ID_EXCEPTION';
}
