<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;

class Onboarding
{
    /** @var string connected status */
    const STATUS_CONNECTED = 'CONNECTED';

    /** @var string disconnected status */
    const STATUS_DISCONNECTED = 'DISCONNECTED';

    /** @var string pending status */
    const STATUS_PENDING = 'PENDING';

    /** @var string */
    const ACTION_FINISH = 'mwc_payments_oauth_onboarding_finish';

    /** @var string onboarding action */
    const ACTION_START = 'mwc_payments_stripe_onboarding_start';

    /** @var string onboarding disconnect action */
    const ACTION_DISCONNECT = 'mwc_payments_stripe_disconnect';

    /**
     * Gets the configured status.
     *
     * @return string
     */
    public static function getStatus() : string
    {
        return (string) Configuration::get('payments.stripe.status', '');
    }

    /**
     * Gets the webhook secret.
     *
     * This secret is passed to the MWC API and used on redirection back to the site
     * with credentials. If no webhook is set, one will be generated and returned.
     *
     * @return string
     */
    public static function getWebhookSecret() : string
    {
        if (! $webhookSecret = Configuration::get('payments.stripe.onboarding.webhookSecret', '')) {
            $webhookSecret = StringHelper::generateUuid4();
            static::setWebhookSecret($webhookSecret);
        }

        return (string) $webhookSecret;
    }

    /**
     * Sets the connection status.
     *
     * @param string $value
     */
    public static function setStatus(string $value)
    {
        update_option('mwc_payments_stripe_status', $value);

        Configuration::set('payments.stripe.status', $value);
    }

    /**
     * Sets the webhook secret.
     *
     * @param string $value
     */
    public static function setWebhookSecret(string $value)
    {
        update_option('mwc_payments_stripe_onboarding_webhookSecret', $value);

        Configuration::set('payments.stripe.onboarding.webhookSecret', $value);
    }

    /**
     * Gets the configuration url.
     *
     * @param string $stripeUri
     * @return string
     * @throws Exception
     */
    public static function getConnectionUrl(string $stripeUri) : string
    {
        $url = add_query_arg('state', json_encode([
            'siteId' => PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getPlatformSiteId(),
            'nonce'  => wp_create_nonce(static::ACTION_FINISH),
        ]), $stripeUri);

        $user = User::getCurrent();

        return add_query_arg([
            'stripe_user[email]'      => $user->getEmail(),
            'stripe_user[first_name]' => $user->getFirstName(),
            'stripe_user[last_name]'  => $user->getLastName(),
            'stripe_user[country]'    => WooCommerceRepository::getBaseCountry(),
            'stripe_user[currency]'   => WooCommerceRepository::getCurrency(),
        ], $url);
    }

    /**
     * Gets the URL to start the onboarding process.
     *
     * @return string
     */
    public static function getStartUrl() : string
    {
        return wp_nonce_url(add_query_arg('action', static::ACTION_START, admin_url('admin.php')), static::ACTION_START);
    }

    /**
     * Gets the URL to Disconnect from Stripe.
     *
     * @return string
     */
    public static function getDisconnectUrl() : string
    {
        return wp_nonce_url(add_query_arg('action', static::ACTION_DISCONNECT, admin_url('admin.php')), static::ACTION_DISCONNECT);
    }
}
