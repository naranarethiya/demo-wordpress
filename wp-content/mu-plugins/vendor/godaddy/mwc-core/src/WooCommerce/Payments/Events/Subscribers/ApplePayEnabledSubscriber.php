<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\PaymentGatewayEnabledEvent;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\ApplePayGateway as ApplePayPaymentGateway;

/**
 * Subscriber for Apple Pay registration event.
 *
 * Registers the merchant with Apple Pay using domain association.
 */
class ApplePayEnabledSubscriber implements SubscriberContract
{
    /**
     * Handles the event.
     *
     * @param PaymentGatewayEnabledEvent|EventContract $event
     */
    public function handle(EventContract $event)
    {
        if (! $this->shouldHandle($event)) {
            return;
        }

        $this->maybeRegisterApplePay();
    }

    /**
     * Determines if the event should be handled.
     *
     * @param EventContract $event
     *
     * @return bool
     */
    protected function shouldHandle(EventContract $event) : bool
    {
        if (! Configuration::get('features.apple_pay')) {
            return false;
        }

        return $event instanceof PaymentGatewayEnabledEvent && 'godaddy-payments-apple-pay' === ArrayHelper::get($event->getData(), 'paymentGateway.id');
    }

    /**
     * Registers for Apple Pay if not already.
     */
    protected function maybeRegisterApplePay() : void
    {
        // bail if Apple Pay has already been registered for the current domain
        if (SiteRepository::getDomain() === get_option(ApplePayPaymentGateway::REGISTERED_DOMAIN_OPTION_NAME)) {
            return;
        }

        ApplePayPaymentGateway::registerDomainWithApple();
    }
}
