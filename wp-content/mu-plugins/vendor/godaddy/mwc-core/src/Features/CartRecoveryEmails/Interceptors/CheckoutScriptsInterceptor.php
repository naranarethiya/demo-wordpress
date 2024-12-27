<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractEnqueueScriptsInterceptor;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Settings\OptOutSetting;

/**
 * Interceptor for WooCommerce cart updates.
 */
class CheckoutScriptsInterceptor extends AbstractEnqueueScriptsInterceptor
{
    /**
     * Enqueues the JS file that defines the JS handler.
     *
     * @throws Exception
     */
    public function enqueueJs()
    {
        Enqueue::script()
               ->setHandle('mwc-cart-recovery-emails-checkout')
               ->setSource(WordPressRepository::getAssetsUrl('js/features/cart-recovery-emails/frontend/checkout.js'))
               ->setDependencies(['jquery'])
               ->setVersion(Configuration::get('mwc.version'))
               ->execute();
    }

    /**
     * Gets the name of the JS class for the handler.
     *
     * @return string
     */
    public function getJsHandlerClassName() : string
    {
        return 'MWCCartRecoveryEmailsCheckoutHandler';
    }

    /**
     * Gets the name of the JS event triggered when the handler is loaded.
     *
     * @return string
     */
    public function getJsLoadedEventName() : string
    {
        return 'mwc_cart_recovery_emails_checkout_handler_loaded';
    }

    /**
     * Gets the name of the JS variable that should hold an instance of the handler.
     *
     * @return string
     */
    public function getJsHandlerObjectName() : string
    {
        return 'mwc_cart_recovery_emails_checkout_handler';
    }

    /**
     * Returns true if the JS should be enqueued and false otherwise.
     *
     * @return bool
     */
    public function shouldEnqueueJs() : bool
    {
        return WooCommerceRepository::isCheckoutPage();
    }

    /**
     * Gets the args for the handler constructor.
     *
     * @return array
     */
    public function getJsHandlerArgs() : array
    {
        return [
            'ajaxUrl'                                        => SiteRepository::getAdminUrl('admin-ajax.php'),
            'checkoutEmailUpdatedAction'                     => AjaxInterceptor::UPDATE_CHECKOUT_EMAIL_ACTION,
            'checkoutEmailUpdatedNonce'                      => wp_create_nonce(AjaxInterceptor::UPDATE_CHECKOUT_EMAIL_ACTION),
            'isUserLoggedIn'                                 => ! empty(User::getCurrent()),
            'cartRecoveryEmailsOptOutPreferenceFieldName'    => (new OptOutSetting())->getName(),
            'cartRecoveryEmailsOptOutPreferenceToggleAction' => AjaxInterceptor::UPDATE_OPT_OUT_PREFERENCE_ACTION,
            'cartRecoveryEmailsOptOutPreferenceToggleNonce'  => wp_create_nonce(AjaxInterceptor::UPDATE_OPT_OUT_PREFERENCE_ACTION),
        ];
    }
}
