<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe\DataStores\WooCommerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\SessionRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\Contracts\DataStoreContract;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\MissingPaymentIntentException;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Models\PaymentIntent;

/**
 * Modifies PaymentIntent session data.
 */
class SessionPaymentIntentDataStore implements DataStoreContract
{
    use CanGetNewInstanceTrait;

    /** @var string session key to be used in the WooCommerce session to store the PaymentIntent Id */
    const SESSION_KEY_PAYMENT_INTENT_ID = 'payment_intent_id';

    /**
     * Deletes the PaymentIntent Id from the user session.
     *
     * @param PaymentIntent|null $paymentIntent
     *
     * @return bool
     * @throws MissingPaymentIntentException|Exception
     */
    public function delete(?PaymentIntent $paymentIntent = null) : bool
    {
        if (null === $paymentIntent) {
            throw new MissingPaymentIntentException('Payment Intent is missing.');
        }

        if ($paymentIntent->getId() === SessionRepository::get(self::SESSION_KEY_PAYMENT_INTENT_ID)) {
            SessionRepository::set(self::SESSION_KEY_PAYMENT_INTENT_ID, '');

            return true;
        }

        return false;
    }

    /**
     * Reads the PaymentIntent Id from the user session.
     *
     * @return PaymentIntent|null
     * @throws Exception
     */
    public function read() : ?PaymentIntent
    {
        if ($sessionId = SessionRepository::get(self::SESSION_KEY_PAYMENT_INTENT_ID)) {
            return (new PaymentIntent())->setId($sessionId);
        }

        return null;
    }

    /**
     * Saves the PaymentIntent Id to the user session.
     *
     * @param PaymentIntent|null $paymentIntent
     *
     * @return PaymentIntent
     *
     * @throws MissingPaymentIntentException|Exception
     */
    public function save(?PaymentIntent $paymentIntent = null) : PaymentIntent
    {
        if (null === $paymentIntent) {
            throw new MissingPaymentIntentException('Payment Intent is missing.');
        }

        SessionRepository::set(self::SESSION_KEY_PAYMENT_INTENT_ID, $paymentIntent->getId());

        return $paymentIntent;
    }
}
