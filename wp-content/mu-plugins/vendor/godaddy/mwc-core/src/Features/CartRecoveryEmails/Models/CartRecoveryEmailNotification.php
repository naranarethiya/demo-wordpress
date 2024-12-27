<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models;

use DateInterval;
use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\ContainerException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Cart;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Core\Configuration\RuntimeConfigurationFactory;
use GoDaddy\WordPress\MWC\Core\Email\EmailService;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\CartRecoveryEmails;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Contracts\CheckoutEmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\DataProviders\CheckoutDataProvider;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Exceptions\CartRecoveryException;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Settings\OptOutSetting;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Traits\CanBuildPreviewCartTrait;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Traits\IsCheckoutEmailNotificationTrait;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\ConditionalEmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\ConsecutiveEmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\DataProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\DelayableEmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\EmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\EmailNotificationSetting;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\IsDelayableEmailNotificationTrait;
use RuntimeException;

/**
 * Email notification for cart recovery emails.
 */
class CartRecoveryEmailNotification extends EmailNotification implements
    CheckoutEmailNotificationContract,
    ConditionalEmailNotificationContract,
    DelayableEmailNotificationContract,
    ConsecutiveEmailNotificationContract
{
    use IsCheckoutEmailNotificationTrait;
    use IsDelayableEmailNotificationTrait;
    use CanBuildPreviewCartTrait;

    /** @var string */
    protected $id = 'cart_recovery';

    /** @var string[] */
    protected $categories = ['cart_recovery'];

    /** @var int position of this email in the Cart Recovery campaign */
    protected $position = 1;

    /**
     * Configures the email notification.
     */
    public function __construct()
    {
        $this->setName($this->getId())
            ->setLabel(__('Cart Recovery', 'mwc-core'))
            ->setDescription(__("Send a reminder email to customers that left products in their cart but didn't complete the checkout.", 'mwc-core'));
    }

    /**
     * Gets the email notification's initial data providers.
     *
     * @return DataProviderContract[]
     * @throws Exception
     */
    protected function getInitialDataProviders() : array
    {
        return TypeHelper::arrayOf(
            ArrayHelper::combine(parent::getInitialDataProviders(), [
                new CheckoutDataProvider($this),
            ]),
            DataProviderContract::class,
            false
        );
    }

    /**
     * Gets the email notification's initial settings.
     *
     * @return EmailNotificationSetting[]
     * @throws Exception
     */
    protected function getInitialSettings() : array
    {
        return [
            $this->getEnabledSettingObject(),
            $this->getSubjectSettingObject(),
            $this->getPreviewTextSettingObject(),
            $this->getDelayValueSettingObject(),
            $this->getDelayUnitSettingObject(),
        ];
    }

    /**
     * Gets the SubjectSettingObject from the parent and sets a default and isRequire.
     *
     * @return EmailNotificationSetting
     */
    protected function getSubjectSettingObject() : EmailNotificationSetting
    {
        return parent::getSubjectSettingObject()->setDefault(__('Hey {{customer_first_name}}, did you forget something?', 'mwc-core'))->setIsRequired(true);
    }

    /**
     * Gets the PreviewTextSettingObject from the parent and sets a default.
     *
     * @return EmailNotificationSetting
     * @throws Exception
     */
    protected function getPreviewTextSettingObject() : EmailNotificationSetting
    {
        return parent::getPreviewTextSettingObject()->setDefault(__('Did you forget something?', 'mwc-core'));
    }

    /** {@inheritdoc} */
    public function getPosition() : int
    {
        return $this->position;
    }

    /**
     * Determines whether the email notification is available.
     *
     * @return bool
     */
    public function isAvailable() : bool
    {
        return CartRecoveryEmails::shouldLoad() && $this->isAllowedInCampaign();
    }

    /**
     * Determines whether this email is allowed in the Cart Recovery campaign.
     *
     * @return bool
     */
    protected function isAllowedInCampaign() : bool
    {
        try {
            $runtimeConfiguration = RuntimeConfigurationFactory::getInstance()->getCartRecoveryEmailsRuntimeConfiguration();
        } catch (CartRecoveryException|ContainerException|RuntimeException $e) {
            return false;
        }

        return $runtimeConfiguration->isCartRecoveryEmailAllowed($this->position);
    }

    /**
     * Determines whether the email notification is enabled.
     *
     * Disables the notification if the MWC Emails Service is not available (for instance if the email sender is not verified).
     *
     * @return bool
     */
    public function isEnabled() : bool
    {
        return parent::isEnabled() && EmailService::shouldLoad();
    }

    /**
     * Determines whether the email should be sent.
     *
     * This should be true when:
     * - the checkout is recoverable {@see Checkout::getStatus()} (the email has been scheduled to be sent)
     * - there is a cart hash (meaning the cart is not empty)
     * - the customer has not opted out from receiving this email
     *
     * @return bool
     * @throws Exception
     */
    public function shouldSend() : bool
    {
        $checkout = $this->getCheckout();

        return $checkout
            && CartRecoveryEmails::isCartRecoveryEmailNotificationEnabled()
            && $this->isCheckoutStatusEligible($checkout)
            && ! empty($checkout->getWcCartHash())
            && empty(OptOutSetting::get($checkout->getEmailAddress()))
            && ! $this->recipientHasPlacedRecentOrder();
    }

    /**
     * Determines whether the status of the given {@see Checkout} instance is eligible for sending this email notification.
     *
     * @param Checkout $checkout
     * @return bool
     */
    protected function isCheckoutStatusEligible(Checkout $checkout) : bool
    {
        return $checkout->isRecoverable();
    }

    /**
     * Determines whether the recipient of the email notification has placed a recent order.
     *
     * @return bool true if the recipient has placed a paid order within the past 5 days
     * @throws Exception
     */
    protected function recipientHasPlacedRecentOrder() : bool
    {
        $checkout = $this->getCheckout();

        return $checkout && $checkout->getEmailAddress() && ! empty(OrdersRepository::query([
            'limit'        => 1,
            'return'       => 'ids',
            'type'         => 'shop_order',
            'customer'     => $checkout->getEmailAddress(),
            'status'       => OrdersRepository::getPaidStatuses(),
            'date_created' => '>='.current_datetime()->modify('-5 days')->format('Y-m-d'),
        ]));
    }

    /**
     * Determines the DateTime the email should be scheduled for.
     *
     * @return DateTime|null
     */
    public function sendAt() : ?DateTime
    {
        $checkout = $this->getCheckout();

        if (! $checkout || ! $checkout->getUpdatedAt()) {
            return null;
        }

        try {
            $delay = DateInterval::createFromDateString("{$this->getDelayValue()} {$this->getDelayUnit()}");
        } catch (Exception $exception) {
            // configured delay is invalid, ignore it
            return null;
        }

        return $delay ? (clone $checkout->getUpdatedAt())->add($delay) : null;
    }

    /**
     * Gets preview data for the custom components that represent non-editable parts for previewing the email notification.
     *
     * @return array
     * @throws Exception
     */
    protected function getAdditionalPreviewData() : array
    {
        $previewCustomer = (new User())
            ->setFirstName('John')
            ->setLastName('Doe');

        $previewCheckout = (new Checkout())
            ->setCart($this->getPreviewCart())
            ->setCustomer($previewCustomer);

        $this->setCheckout($previewCheckout);

        return $this->getData();
    }
}
