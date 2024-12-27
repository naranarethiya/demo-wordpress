<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\IsCommerceExceptionTrait;
use Throwable;

/**
 * Exception thrown when a product is unexpectedly missing a remote ID.
 */
class MissingProductRemoteIdException extends BaseException implements CommerceExceptionContract
{
    use IsCommerceExceptionTrait;

    protected string $errorCode = 'COMMERCE_MISSING_PRODUCT_REMOTE_ID_EXCEPTION';

    /**
     * Creates a new instance of this exception using a default message.
     *
     * @param Throwable|null $previous
     * @return static
     */
    public static function withDefaultMessage(?Throwable $previous = null)
    {
        return new static('The returned product data has no UUID.', $previous);
    }
}
