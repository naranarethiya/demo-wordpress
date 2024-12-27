<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails;

use Exception;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\DataStores\WooCommerce\CheckoutDataStore;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models\CartRecoveryEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models\Checkout;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Services\CartRecoveryEmailsCampaignRulesService;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\ConsecutiveEmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailNotificationCampaignStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataStores\EmailNotificationDataStore;

class CartRecoveryEmailNotificationCampaignStrategy implements EmailNotificationCampaignStrategyContract
{
    /** @var Checkout The checkout instance that triggered the campaign */
    protected $checkout;

    /** @var CartRecoveryEmailsCampaignRulesService */
    protected $campaignRulesService;

    /** @var ?CartRecoveryEmailNotification[] email notifications associated with this strategy sorted by position in ascending order */
    protected $emailNotifications = null;

    /**
     * Constructor.
     *
     * @param Checkout $checkout
     * @param CartRecoveryEmailsCampaignRulesService $campaignRulesService
     */
    final public function __construct(Checkout $checkout, CartRecoveryEmailsCampaignRulesService $campaignRulesService)
    {
        $this->checkout = $checkout;
        $this->campaignRulesService = $campaignRulesService;
    }

    /**
     * Creates an instance based on the given checkout object.
     *
     * @param Checkout $checkout
     * @return static
     */
    public static function fromCheckout(Checkout $checkout) : CartRecoveryEmailNotificationCampaignStrategy
    {
        return new static($checkout, CartRecoveryEmailsCampaignRulesService::getNewInstance($checkout, CheckoutDataStore::getNewInstance()));
    }

    /**
     * Gets the list of email notifications associated with this strategy.
     *
     * @return CartRecoveryEmailNotification[]
     */
    public function getEmailNotifications() : array
    {
        return $this->emailNotifications ?? $this->loadEmailNotifications();
    }

    /**
     * Loads all known cart recovery email notifications associated with this strategy.
     *
     * @return CartRecoveryEmailNotification[]
     */
    protected function loadEmailNotifications() : array
    {
        $emailNotifications = $this->fetchEmailNotifications();

        usort($emailNotifications, function (CartRecoveryEmailNotification $a, CartRecoveryEmailNotification $b) {
            return $a->getPosition() - $b->getPosition();
        });

        return $this->emailNotifications = $emailNotifications;
    }

    /**
     * Retrieves all known cart recovery email notifications associated with this strategy.
     *
     * @return CartRecoveryEmailNotification[]
     */
    protected function fetchEmailNotifications() : array
    {
        foreach (['cart_recovery', 'second_cart_recovery', 'third_cart_recovery'] as $emailId) {
            $emailNotifications[$emailId] = $this->getEmailNotificationById($emailId);
        }

        return array_filter($emailNotifications);
    }

    /**
     * Gets email notification object by the given ID.
     *
     * @param string $emailId
     * @return CartRecoveryEmailNotification|null
     */
    protected function getEmailNotificationById(string $emailId) : ?CartRecoveryEmailNotification
    {
        try {
            $emailNotification = EmailNotificationDataStore::getNewInstance()->read($emailId);
        } catch (Exception $exception) {
            return null;
        }

        return $emailNotification instanceof CartRecoveryEmailNotification ? $emailNotification : null;
    }

    /**
     * Gets the first email notification of the campaign.
     *
     * @return CartRecoveryEmailNotification|null
     */
    public function getFirstEmailNotification() : ?ConsecutiveEmailNotificationContract
    {
        $notifications = $this->getEmailNotifications();

        if (! $emailNotification = reset($notifications)) {
            return null;
        }

        return $this->prepareEmailNotification($emailNotification);
    }

    /**
     * Prepares the given email notification instance.
     *
     * @param CartRecoveryEmailNotification $emailNotification
     * @return CartRecoveryEmailNotification
     */
    protected function prepareEmailNotification(CartRecoveryEmailNotification $emailNotification) : CartRecoveryEmailNotification
    {
        return $emailNotification->setCheckout($this->checkout);
    }

    /**
     * Gets the email notification that comes immediately after the given email notification.
     *
     * @param ConsecutiveEmailNotificationContract $emailNotification
     * @return CartRecoveryEmailNotification|null
     */
    public function getNextEmailNotificationAfter(ConsecutiveEmailNotificationContract $emailNotification) : ?ConsecutiveEmailNotificationContract
    {
        foreach ($this->getEmailNotifications() as $item) {
            if ($item->getPosition() > $emailNotification->getPosition()) {
                return $this->prepareEmailNotification($item);
            }
        }

        return null;
    }

    /**
     * Gets the last email notification in the campaign.
     *
     * @return CartRecoveryEmailNotification|null
     */
    protected function getLastEmailNotification() : ?CartRecoveryEmailNotification
    {
        $notifications = $this->getEmailNotifications();

        if (! $emailNotification = end($notifications)) {
            return null;
        }

        return $this->prepareEmailNotification($emailNotification);
    }

    /**
     * {@inheritDoc}
     */
    public function shouldStartCampaign() : bool
    {
        return $this->campaignRulesService->isCampaignEnabled() &&
            $this->campaignRulesService->isCartHashAvailable() &&
            $this->campaignRulesService->isEmailAddressAvailable() &&
            ! $this->campaignRulesService->isEmailAlreadyScheduledForCurrentCheckout() &&
            $this->canAddEmailAddressToCampaign();
    }

    /**
     * Checks if we can add email address to this strategy.
     *
     * @return bool
     */
    protected function canAddEmailAddressToCampaign() : bool
    {
        if (! $this->campaignRulesService->isEmailAlreadyScheduledForOtherCheckout()) {
            return true;
        }

        if (! $lastEmailNotification = $this->getLastEmailNotification()) {
            return false;
        }

        return $this->campaignRulesService->isSendWaitPeriodOverFor($lastEmailNotification);
    }
}
