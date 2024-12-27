<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models\Checkout;

/**
 * A contract for email notifications that need to handle the checkout object.
 */
interface CheckoutEmailNotificationContract
{
    /**
     * Gets the checkout object.
     *
     * @return Checkout|null
     */
    public function getCheckout();

    /**
     * Sets the checkout object.
     *
     * @param Checkout $value
     * @return self
     */
    public function setCheckout(Checkout $value);
}
