<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\IsCommerceExceptionTrait;

class OrderReservationsCreateFailedException extends SentryException implements CommerceExceptionContract
{
    use IsCommerceExceptionTrait;

    protected string $errorCode = 'COMMERCE_ORDER_RESERVATIONS_CREATE_FAILED_EXCEPTION';
}
