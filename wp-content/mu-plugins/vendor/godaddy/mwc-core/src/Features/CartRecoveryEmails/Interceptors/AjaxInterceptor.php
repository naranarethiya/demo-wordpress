<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseTableDoesNotExistException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\SanitizationHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\ValidationHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\SessionRepository;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\DataStores\WooCommerce\CheckoutDataStore;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Exceptions\CheckoutException;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models\Checkout;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Repositories\WooCommerce\CheckoutRepository;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Settings\OptOutSetting;

/**
 * Interceptor to handle the AJAX request to update the checkout email address.
 */
class AjaxInterceptor extends AbstractInterceptor
{
    /** @var string the action used to update the email address for the current session */
    const UPDATE_CHECKOUT_EMAIL_ACTION = 'mwc_update_checkout_email';

    /** @var string the action used to toggle the opt-out preference for the current user */
    const UPDATE_OPT_OUT_PREFERENCE_ACTION = 'mwc_toggle_cart_recovery_emails_opt_out';

    /**
     * Adds the hook to register the AJAX handler.
     *
     * @throws Exception
     */
    public function addHooks()
    {
        Register::action()
            ->setGroup('wp_ajax_nopriv_'.static::UPDATE_CHECKOUT_EMAIL_ACTION)
            ->setHandler([$this, 'updateCheckoutEmail'])
            ->execute();

        Register::action()
            ->setGroup('wp_ajax_'.static::UPDATE_CHECKOUT_EMAIL_ACTION)
            ->setHandler([$this, 'updateCheckoutEmail'])
            ->execute();

        Register::action()
            ->setGroup('wp_ajax_nopriv_'.static::UPDATE_OPT_OUT_PREFERENCE_ACTION)
            ->setHandler([$this, 'toggleOptOutPreference'])
            ->execute();

        Register::action()
            ->setGroup('wp_ajax_'.static::UPDATE_OPT_OUT_PREFERENCE_ACTION)
            ->setHandler([$this, 'toggleOptOutPreference'])
            ->execute();
    }

    /**
     * Updates the email address used for the current checkout session.
     *
     * @internal
     *
     * @throws Exception
     */
    public function updateCheckoutEmail()
    {
        check_ajax_referer(static::UPDATE_CHECKOUT_EMAIL_ACTION, 'nonce');

        try {
            // check if the customer is logged in
            if (! empty(User::getCurrent())) {
                // for logged in customers, the registered email takes precedence over the email used in the Checkout form
                (new Response())->setBody(['success' => true])->send();

                return;
            }

            $email = SanitizationHelper::input(ArrayHelper::get($_POST, 'email'));

            if (! empty($checkoutId = SessionRepository::get(CheckoutRepository::SESSION_KEY_CHECKOUT_ID))) {
                // get the checkout object stored in the custom table, including the session data
                $checkout = CheckoutDataStore::getNewInstance()->read($checkoutId);
            }

            if (empty($checkout)) {
                // clear the id because the checkout record was deleted
                SessionRepository::set(CheckoutRepository::SESSION_KEY_CHECKOUT_ID, null);
                // get a fresh checkout object from the session data
                $checkout = CheckoutRepository::getFromSession();
            }

            if (! $checkout instanceof Checkout) {
                throw new CheckoutException('No checkout found.');
            }

            $checkout->setEmailAddress($email)->save();

            SessionRepository::set(CheckoutRepository::SESSION_KEY_CHECKOUT_ID, $checkout->getId());

            (new Response())->setBody(['success' => true])->send();
        } catch (WordPressDatabaseTableDoesNotExistException $exception) {
            // do not report to Sentry to reduce noise (we are already reporting when the table creation fails)
        } catch (Exception $exception) {
            new SentryException($exception->getMessage(), $exception);

            (new Response())->setBody(['success' => false, 'data' => $exception->getMessage()])->send();
        }
    }

    /**
     * Processes the user's opt-out preference.
     *
     * @internal
     *
     * @throws Exception
     */
    public function toggleOptOutPreference()
    {
        check_ajax_referer(static::UPDATE_OPT_OUT_PREFERENCE_ACTION, 'nonce');

        try {
            $email = $this->getOptOutEmailAddress();

            // bail if the email address is invalid or incomplete without having to trigger a Sentry error thereafter
            if (! ValidationHelper::isEmail($email)) {
                (new Response())->setBody(['success' => false, 'data' => 'Empty or invalid email address.'])->send();

                return;
            }

            if (filter_var(ArrayHelper::get($_POST, 'optOut'), FILTER_VALIDATE_BOOLEAN)) {
                $isSuccessful = OptOutSetting::getNewInstance()->save($email);
            } else {
                $isSuccessful = OptOutSetting::getNewInstance()->delete($email);
            }

            (new Response())->setBody(['success' => $isSuccessful])->send();
        } catch (WordPressDatabaseTableDoesNotExistException $e) {
            // do not report to Sentry to reduce noise (we are already reporting when the table creation fails)
        } catch (Exception $exception) {
            new SentryException($exception->getMessage(), $exception);

            (new Response())->setBody(['success' => false, 'data' => $exception->getMessage()])->send();
        }
    }

    /**
     * Retrieves the email address to use when processing the opt-out toggle.
     *
     * If the user is logged in then their account email address takes precedence over what was supplied in the AJAX request.
     *
     * @return string
     */
    protected function getOptOutEmailAddress() : string
    {
        if ($user = User::getCurrent()) {
            return $user->getEmail() ?: '';
        } else {
            return SanitizationHelper::input(ArrayHelper::get($_POST, 'email', ''));
        }
    }
}
