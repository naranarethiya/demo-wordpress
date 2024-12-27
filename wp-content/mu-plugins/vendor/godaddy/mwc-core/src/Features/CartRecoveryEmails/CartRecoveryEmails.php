<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Features\AbstractFeature;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Email\EmailService;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Interceptors\AjaxInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Interceptors\CartRecoveryInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Interceptors\CartUpdatedInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Interceptors\CheckoutInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Interceptors\CheckoutScriptsInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Interceptors\OrderInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Interceptors\SessionExpirationInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models\CartRecoveryEmailNotification;

/**
 * The Cart Recovery Emails feature loader.
 */
class CartRecoveryEmails extends AbstractFeature
{
    use HasComponentsTrait;

    /** @var string endpoint used in WooCommerce to restore the cart */
    const CART_RECOVERY_ENDPOINT = 'mwc_cart_recovery';

    /** @var string transient key placeholder to temporarily disable the feature */
    const TRANSIENT_DISABLE_FEATURE = 'mwc_cart_recovery_disabled';

    /** @var array alphabetically ordered list of components to load */
    protected $componentClasses = [
        AjaxInterceptor::class,
        Lifecycle::class,
        CartUpdatedInterceptor::class,
        CartRecoveryInterceptor::class,
        CheckoutInterceptor::class,
        CheckoutScriptsInterceptor::class,
        OrderInterceptor::class,
        SessionExpirationInterceptor::class,
    ];

    /**
     * Determines if the feature should load.
     *
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        if (! empty(get_transient(CartRecoveryEmails::TRANSIENT_DISABLE_FEATURE))) {
            return false;
        }

        return parent::shouldLoad();
    }

    /**
     * {@inheritdoc}
     */
    public static function getName() : string
    {
        return 'cart_recovery_emails';
    }

    /**
     * Initializes the feature.
     *
     * @throws Exception
     */
    public function load()
    {
        $this->loadComponents();
    }

    /**
     * Determines whether customers are allowed to opt out from receiving cart recovery emails.
     *
     * @return bool
     *
     * @throws Exception
     */
    public static function allowsCustomersOptOut() : bool
    {
        return static::isCartRecoveryEmailNotificationEnabled()
            && Configuration::get('features.cart_recovery_emails.allow_customers_opt_out', true);
    }

    /**
     * Gets the WooCommerce API URL for the MWC Cart Recovery feature.
     *
     * @return string
     */
    public static function getWooCommerceCartRecoveryEndpointUrl() : string
    {
        return rtrim(WooCommerceRepository::getApiUrl(self::CART_RECOVERY_ENDPOINT), '/');
    }

    /**
     * Determines whether the Cart Recovery Email Notification is enabled.
     *
     * This flag is updated by the Emails settings.
     *
     * The notification is considered disabled if the MWC Emails Service is not available
     * (for instance if the email sender is not verified).
     *
     * @see CartRecoveryEmailNotification::isEnabled()
     *
     * @return bool
     */
    public static function isCartRecoveryEmailNotificationEnabled() : bool
    {
        return 'yes' === get_option('mwc_cart_recovery_email_notification_enabled')
            && EmailService::shouldLoad();
    }
}
