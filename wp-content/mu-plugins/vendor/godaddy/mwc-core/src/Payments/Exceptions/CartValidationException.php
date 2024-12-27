<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;

/**
 * An exception to be thrown when a cart validation error occurs.
 */
class CartValidationException extends BaseException
{
    /** @var int exception code */
    protected $code = 400;

    /** @var string validation error code */
    protected $errorCode;

    /** @var string the validated field */
    protected $field = null;

    /**
     * Constructor.
     *
     * @param string $message exception message
     * @param string $errorCode validation error code
     * @param string|null $field validated field
     */
    public function __construct(string $message, string $errorCode = '', string $field = null)
    {
        $this->errorCode = $errorCode;
        $this->field = $field;

        parent::__construct($message);
    }

    /**
     * Gets the validation error code.
     *
     * @return string
     */
    public function getErrorCode() : string
    {
        return $this->errorCode;
    }

    /**
     * Gets the validated field.
     *
     * @return string|null
     */
    public function getField() : ?string
    {
        return $this->field;
    }
}
