<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\CorePaymentGateways;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\ApplePayGateway as ApplePayPaymentGateway;

/**
 * A WooCommerce interceptor to hook on product actions and filters.
 */
class DomainChangeInterceptor extends AbstractInterceptor
{
    /**
     * Determines whether the interceptor should be loaded.
     *
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        return WooCommerceRepository::isWooCommerceActive() && true === Configuration::get('features.apple_pay');
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'maybeRegisterApplePay'])
            ->execute();
    }

    /**
     * Registers the site domain with Apple Pay if it has been changed and Apple Pay is enabled.
     *
     * TODO: this will trigger a registration retry on each page load
     *
     * @throws Exception|SentryException
     * @internal
     */
    public function maybeRegisterApplePay() : void
    {
        // bail if Apple Pay has already been registered for the current domain
        if (SiteRepository::getDomain() === get_option(ApplePayPaymentGateway::REGISTERED_DOMAIN_OPTION_NAME)) {
            return;
        }

        if (($gateway = CorePaymentGateways::getWalletGatewayInstance('godaddy-payments-apple-pay')) && wc_string_to_bool($gateway->enabled)) {
            ApplePayPaymentGateway::registerDomainWithApple();
        }
    }
}
