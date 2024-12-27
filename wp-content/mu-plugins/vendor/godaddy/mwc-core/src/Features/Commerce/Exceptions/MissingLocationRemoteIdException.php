<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\IsCommerceExceptionTrait;
use Throwable;

/**
 * Exception thrown when a location is unexpectedly missing a remote ID.
 */
class MissingLocationRemoteIdException extends BaseException implements CommerceExceptionContract
{
    use IsCommerceExceptionTrait;

    protected string $errorCode = 'COMMERCE_MISSING_LOCATION_REMOTE_ID_EXCEPTION';

    /**
     * Creates a new instance of this exception using a default message.
     *
     * @param Throwable|null $previous
     * @return static
     */
    public static function withDefaultMessage(?Throwable $previous = null)
    {
        return new static('The returned location data has no UUID.', $previous);
    }
}
