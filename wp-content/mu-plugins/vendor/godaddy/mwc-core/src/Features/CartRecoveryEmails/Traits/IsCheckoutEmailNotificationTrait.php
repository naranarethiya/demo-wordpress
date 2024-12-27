<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Traits;

use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Contracts\CheckoutEmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models\Checkout;

/**
 * A trait for email notifications that need to handle the checkout object.
 *
 * @see CheckoutEmailNotificationContract
 */
trait IsCheckoutEmailNotificationTrait
{
    /** @var Checkout|null */
    protected $checkout;

    /**
     * Gets the checkout object.
     *
     * @return Checkout|null
     */
    public function getCheckout()
    {
        return $this->checkout;
    }

    /**
     * Sets the checkout object.
     *
     * @param Checkout $value
     * @return self
     */
    public function setCheckout(Checkout $value)
    {
        $this->checkout = $value;

        return $this;
    }
}
