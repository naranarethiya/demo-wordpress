<?php

namespace GoDaddy\WordPress\MWC\Core\Email\Exceptions;

/**
 * Exception to report to Sentry that an email sender is already taken.
 */
class EmailSenderTakenException extends EmailsServiceException
{
    /** @var int exception code */
    protected $code = 403;

    /** @var string exception error code */
    protected $errorCode = 'EMAIL_ADDRESS_ALREADY_TAKEN';

    /**
     * Gets the exception error code.
     *
     * @return string
     */
    public function getErrorCode() : string
    {
        return $this->errorCode;
    }
}
