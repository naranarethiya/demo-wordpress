<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\IsCommerceExceptionTrait;
use Throwable;

/**
 * Exception thrown after converting local IDs to their remote counterparts, but we unexpectedly end up with no results.
 */
class MissingRemoteIdsAfterLocalIdConversionException extends BaseException implements CommerceExceptionContract
{
    use IsCommerceExceptionTrait;

    protected string $errorCode = 'COMMERCE_MISSING_REMOTE_IDS_AFTER_LOCAL_ID_CONVERSION_EXCEPTION';

    /**
     * Creates a new instance of this exception using a default message.
     *
     * @param Throwable|null $previous
     * @return static
     */
    public static function withDefaultMessage(?Throwable $previous = null) : MissingRemoteIdsAfterLocalIdConversionException
    {
        return new static('No remote IDs found from the supplied list of local IDs.', $previous);
    }
}
