<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notices;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices\ApplePayEnabledNotice;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices\ApplePayNoEnabledPagesNotice;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices\ApplePayRegistrationFailedNotice;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\ApplePayGateway;

class EnqueueApplePayNoticeInterceptor extends AbstractGoDaddyPaymentsNoticeInterceptor
{
    /**
     * Determines whether the component should be loaded or not.
     *
     * @throws Exception
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        if (true !== Configuration::get('payments.applePay.enabled', false) || ! ApplePayGateway::isActive()) {
            return false;
        }

        return parent::shouldLoad();
    }

    /**
     * {@inheritDoc}
     */
    public function enqueueNotice() : void
    {
        if (ApplePayGateway::isDomainRegisteredWithApple()) {
            Notices::enqueueAdminNotice(ApplePayEnabledNotice::getNewInstance());
        } else {
            Notices::enqueueAdminNotice(ApplePayRegistrationFailedNotice::getNewInstance());
        }

        $this->maybeEnqueueNoEnabledPagesNotice();
    }

    /**
     * Enqueues the "no pages enabled" notice if conditions are met.
     *
     * @return void
     */
    protected function maybeEnqueueNoEnabledPagesNotice() : void
    {
        $page = ArrayHelper::get($_GET, 'page');
        $section = ArrayHelper::get($_GET, 'section');

        // only display the following notice on the Apple Pay settings page
        if ('wc-settings' === $page && 'godaddy-payments-apple-pay' === $section && ! ApplePayGateway::hasEnabledPages()) {
            Notices::enqueueAdminNotice(ApplePayNoEnabledPagesNotice::getNewInstance());
        }
    }
}
