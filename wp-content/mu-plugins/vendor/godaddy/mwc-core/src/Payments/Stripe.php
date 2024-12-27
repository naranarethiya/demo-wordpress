<?php

namespace GoDaddy\WordPress\MWC\Core\Payments;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Gateways\AccountGateway;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Models\Account;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Onboarding;
use Stripe\Exception\ApiErrorException;

/**
 * The primary Stripe payments integration class.
 */
class Stripe
{
    /**
     * Gets the connected account object.
     *
     * @return Account|null
     * @throws ApiErrorException
     */
    public static function getAccount() : ?Account
    {
        if ($accountId = static::getAccountId()) {
            return AccountGateway::getNewInstance()->get($accountId);
        }

        return null;
    }

    /**
     * Gets the account ID.
     *
     * @return string|null
     */
    public static function getAccountId() : ?string
    {
        return Configuration::get('payments.stripe.accountId');
    }

    /**
     * Sets the account ID.
     *
     * @param string $value
     */
    public static function setAccountId(string $value)
    {
        Configuration::set('payments.stripe.accountId', $value);

        update_option('mwc_payments_stripe_accountId', $value);
    }

    /**
     * Gets the API public key.
     *
     * @return string|null
     */
    public static function getApiPublicKey() : ?string
    {
        return Configuration::get('payments.stripe.api.publicKey');
    }

    /**
     * Sets the API public key.
     *
     * @param string $value
     */
    public static function setApiPublicKey(string $value)
    {
        Configuration::set('payments.stripe.api.publicKey', $value);

        update_option('mwc_payments_stripe_api_publicKey', $value);
    }

    /**
     * Gets the API secret key.
     *
     * @return string|null
     */
    public static function getApiSecretKey() : ?string
    {
        return Configuration::get('payments.stripe.api.secretKey');
    }

    /**
     * Sets the API secret key.
     *
     * @param string $value
     */
    public static function setApiSecretKey(string $value)
    {
        Configuration::set('payments.stripe.api.secretKey', $value);

        update_option('mwc_payments_stripe_api_secretKey', $value);
    }

    /**
     * Gets the Stripe Dashboard URL.
     *
     * @return string
     */
    public static function getDashboardUrl() : string
    {
        return 'https://dashboard.stripe.com/';
    }

    /**
     * Determines if Stripe is fully connected.
     *
     * @return bool
     */
    public static function isConnected() : bool
    {
        return Onboarding::STATUS_CONNECTED === Onboarding::getStatus() &&
               static::getAccountId() &&
               static::getApiPublicKey() &&
               static::getApiSecretKey();
    }

    /**
     * Gets the list of payment method types that can be saved for future payments.
     *
     * @return string[]
     */
    public static function getReusablePaymentMethodTypes() : array
    {
        return [
            'acss_debit',
            'au_becs_debit',
            'bacs_debit',
            'bancontact',
            'blik',
            'boleto',
            'card',
            'card_present',
            'ideal',
            'link',
            'sepa_debit',
            'sofort',
            'us_bank_account',
        ];
    }
}
