<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use Throwable;

/**
 * An exception to be thrown when an order from a cart is invalid.
 */
class CartOrderAdapterException extends BaseException
{
    /**
     * Constructor.
     *
     * @param string $message
     * @param int|null $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message, ?int $code = null, ?Throwable $previous = null)
    {
        if ($code) {
            $this->code = $code;
        }

        parent::__construct($message, $previous);
    }
}
