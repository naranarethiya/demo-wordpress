<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseTableDoesNotExistException;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\CartRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\SessionRepository;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\DataStores\WooCommerce\CheckoutDataStore;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Repositories\WooCommerce\CheckoutRepository;
use WC_Cart_Session;

/**
 * Interceptor for WooCommerce cart updates.
 */
class CartUpdatedInterceptor extends AbstractInterceptor
{
    /**
     * Adds hooks.
     *
     * @throws Exception
     */
    public function addHooks()
    {
        /* @see WC_Cart_Session::set_session() */
        Register::action()
            ->setGroup('woocommerce_cart_updated')
            ->setHandler([$this, 'saveCheckout'])
            ->setPriority(PHP_INT_MAX)
            ->execute();
    }

    /**
     * Saves or updates the checkout data for the customer or guest.
     *
     * @internal
     *
     * @throws Exception
     */
    public function saveCheckout()
    {
        try {
            if (! empty($checkoutId = SessionRepository::get(CheckoutRepository::SESSION_KEY_CHECKOUT_ID))) {
                // checkout is already saved, get the checkout object stored in the custom table, including the session data
                $checkout = CheckoutDataStore::getNewInstance()->read($checkoutId);
            }

            if (empty($checkout)) {
                // clear the id because the checkout record was deleted
                SessionRepository::set(CheckoutRepository::SESSION_KEY_CHECKOUT_ID, null);
                // get a fresh checkout object from the session data
                $checkout = CheckoutRepository::getFromSession();
            }

            if (empty($checkout)) {
                return;
            }

            // gets the current WC cart hash, that will be different if the cart contents change
            $wcCurrentCartHash = CartRepository::getHash() ?: '';

            if (! $checkout->isNew()) {
                $savedHash = $checkout->getWcCartHash();

                // bail if the cart contents have not changed
                if (hash_equals($wcCurrentCartHash, $savedHash)) {
                    return;
                }
            }

            $checkout->setWcCartHash($wcCurrentCartHash);

            if (! empty($user = User::getCurrent())) {
                // for logged-in customers, their registered email always takes precedence
                $checkout->setEmailAddress($user->getEmail());
            }

            $checkout->save();

            SessionRepository::set(CheckoutRepository::SESSION_KEY_CHECKOUT_ID, $checkout->getId());
        } catch (WordPressDatabaseTableDoesNotExistException $exception) {
            // do not report to Sentry to reduce noise (we are already reporting when the table creation fails)
        } catch (Exception $exception) {
            new SentryException($exception->getMessage(), $exception);
        }
    }
}
