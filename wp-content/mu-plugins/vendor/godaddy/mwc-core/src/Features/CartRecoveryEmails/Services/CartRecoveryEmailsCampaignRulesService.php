<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Services;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\CartRecoveryEmails;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\DataStores\WooCommerce\CheckoutDataStore;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models\CartRecoveryEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models\Checkout;

class CartRecoveryEmailsCampaignRulesService
{
    use CanGetNewInstanceTrait;

    /** @var Checkout */
    protected $checkout;

    /** @var CheckoutDataStore */
    protected $checkoutDataStore;

    /**
     * Constructor.
     *
     * @param Checkout $checkout
     * @param CheckoutDataStore $checkoutDataStore
     */
    public function __construct(Checkout $checkout, CheckoutDataStore $checkoutDataStore)
    {
        $this->checkout = $checkout;
        $this->checkoutDataStore = $checkoutDataStore;
    }

    /**
     * Determines if cart recovery emails feature enabled or not.
     *
     * @return bool
     */
    public function isCampaignEnabled() : bool
    {
        return CartRecoveryEmails::isCartRecoveryEmailNotificationEnabled();
    }

    /**
     * Determines if the checkout has the hash of the associated cart session.
     *
     * @return bool
     */
    public function isCartHashAvailable() : bool
    {
        return ! empty($this->checkout->getWcCartHash());
    }

    /**
     * Determines whether the checkout has an email address.
     *
     * @return bool
     */
    public function isEmailAddressAvailable() : bool
    {
        return ! empty($this->checkout->getEmailAddress());
    }

    /**
     * Determines whether an email is already scheduled to be sent for the current checkout instance.
     *
     * @return bool
     */
    public function isEmailAlreadyScheduledForCurrentCheckout() : bool
    {
        return $this->checkout->getEmailScheduledAt() !== null;
    }

    /**
     * Determines email notification is already scheduled to be sent for the other checkout process.
     *
     * @return bool
     */
    public function isEmailAlreadyScheduledForOtherCheckout() : bool
    {
        if (! $this->isEmailAddressAvailable()) {
            return false;
        }

        return null !== $this->getMostRecentCheckoutWithScheduledEmailsExcludingCurrent();
    }

    /**
     * Gets the Checkout instance most recently updated that is not the current Checkout instance and also has an email already scheduled.
     *
     * @return Checkout|null
     */
    protected function getMostRecentCheckoutWithScheduledEmailsExcludingCurrent() : ?Checkout
    {
        if (! $checkoutId = $this->checkout->getId()) {
            return null;
        }

        $checkoutCollection = $this->checkoutDataStore->findAllByEmailAddress($this->checkout->getEmailAddress());

        return $checkoutCollection->getMostRecentCheckoutWithScheduledEmailsExcluding($checkoutId);
    }

    /**
     * Determines whether the estimated send date for the given email is at least five days in the past.
     *
     * @param CartRecoveryEmailNotification $emailNotification
     * @return bool
     */
    public function isSendWaitPeriodOverFor(CartRecoveryEmailNotification $emailNotification) : bool
    {
        if (! $checkout = $this->getMostRecentCheckoutWithScheduledEmailsExcludingCurrent()) {
            return false;
        }

        if ($sendAt = $emailNotification->setCheckout($checkout)->sendAt()) {
            return $sendAt->getTimestamp() <= (time() - (int) Configuration::get('features.cart_recovery_emails.send_wait_period'));
        }

        return false;
    }
}
