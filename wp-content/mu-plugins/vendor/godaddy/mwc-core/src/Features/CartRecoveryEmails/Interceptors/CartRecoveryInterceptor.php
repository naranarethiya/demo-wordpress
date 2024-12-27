<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Http\Redirect;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\SessionRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\CartRecoveryEmails;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\DataStores\WooCommerce\CheckoutDataStore;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Exceptions\CartRecoveryException;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models\Checkout;

/**
 * Interceptor to handle the cart recovery link.
 */
class CartRecoveryInterceptor extends AbstractInterceptor
{
    /**
     * Adds the hook to register the WooCommerce API handler.
     *
     * @throws Exception
     */
    public function addHooks()
    {
        Register::action()
            ->setGroup('woocommerce_api_'.CartRecoveryEmails::CART_RECOVERY_ENDPOINT)
            ->setHandler([$this, 'recoverCart'])
            ->execute();
    }

    /**
     * Recovers a cart from a cart recovery link.
     *
     * @internal
     *
     * @throws Exception
     */
    public function recoverCart()
    {
        try {
            $checkoutId = ArrayHelper::get($_REQUEST, 'checkoutId', 0);
            $cartHash = ArrayHelper::get($_REQUEST, 'cartHash', '');

            $checkout = CheckoutDataStore::getNewInstance()->read($checkoutId);

            if (! $checkout) {
                throw new CartRecoveryException('Checkout not found.');
            }

            if ($cartHash !== $checkout->getWcCartHash()) {
                throw new CartRecoveryException('Checkout hash does not match.');
            }

            $this->restoreCheckout($checkout);
        } catch (CartRecoveryException $exception) {
            $this->onCartRecoveryFailed($exception);
        }

        // Always redirect to the Checkout page:
        // WooCommerce will handle redirecting back to the Cart page if the cart is empty
        try {
            Redirect::to(WooCommerceRepository::getCheckoutPageUrl())->execute();
        } catch (Exception $exception) {
            // we shouldn't be throwing an exception at this point since we are in a WordPress hook callback context
        }
    }

    /**
     * Gets the corresponding WooCommerce session for a given Checkout object.
     *
     * @param Checkout $checkout
     * @return array|null
     */
    protected function getWooCommerceSession(Checkout $checkout) : ?array
    {
        $existingSession = SessionRepository::getSessionById($checkout->getWcSessionId());

        return $existingSession
            ? StringHelper::maybeUnserializeRecursively(ArrayHelper::get($existingSession, 'session_value', ''))
            : null;
    }

    /**
     * Restores the checkout for the customer.
     *
     * @param Checkout $checkout
     * @throws CartRecoveryException|Exception
     */
    protected function restoreCheckout(Checkout $checkout)
    {
        $sessionData = $this->getWooCommerceSession($checkout);

        if (empty($sessionData)) {
            throw new CartRecoveryException('WooCommerce session not found.');
        }

        $wc = WooCommerceRepository::getInstance();

        // starts a new session for the current user or guest if not already started
        if (! $wc->session->get_session_cookie()) {
            $wc->session->set_customer_session_cookie(true);
        }

        // copies the saved session data to the current session
        foreach ($sessionData as $key => $value) {
            SessionRepository::set($key, $value);
        }

        // sets the customer email address as the billing email from the one saved in the checkout table,
        // except for registered customers which may be using a different billing email
        if ($wc->customer && ! User::getCurrent()) {
            $wc->customer->set_billing_email($checkout->getEmailAddress());
            $wc->customer->save();
        }

        SessionRepository::set(Checkout::STATUS_SESSION_KEY, Checkout::STATUS_PENDING_RECOVERY);
    }

    /**
     * Handles a fail to recover the cart by sending the exception to Sentry.
     *
     * @param Exception $exception
     * @throws Exception
     */
    protected function onCartRecoveryFailed(Exception $exception)
    {
        new SentryException($exception->getMessage(), $exception);
    }
}
