<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\CardPaymentMethod;

/**
 * An exception to be thrown when a transaction encounters an AVS check failure.
 */
class InvalidTransactionAvsException extends BaseException
{
    /** @var CardPaymentMethod|null payment method stored for exception handlers */
    protected ?CardPaymentMethod $paymentMethod = null;

    /**
     * Gets the card payment method passed with this exception.
     *
     * @return CardPaymentMethod|null
     */
    public function getPaymentMethod() : ?CardPaymentMethod
    {
        return $this->paymentMethod;
    }

    /**
     * Sets the card payment method passed with this exception.
     *
     * @param CardPaymentMethod|null $paymentMethod
     *
     * @return $this
     */
    public function setPaymentMethod(?CardPaymentMethod $paymentMethod) : InvalidTransactionAvsException
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }
}
