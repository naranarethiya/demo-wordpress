<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notices;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices\GooglePayEnabledNotice;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices\GooglePayNoEnabledPagesNotice;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\GooglePayGateway;

class EnqueueGooglePayNoticeInterceptor extends AbstractGoDaddyPaymentsNoticeInterceptor
{
    /**
     * Determines whether the component should be loaded or not.
     *
     * @throws Exception
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        if (true !== Configuration::get('payments.googlePay.enabled', false) || ! GooglePayGateway::isActive()) {
            return false;
        }

        return parent::shouldLoad();
    }

    /**
     * {@inheritDoc}
     */
    public function enqueueNotice() : void
    {
        Notices::enqueueAdminNotice(GooglePayEnabledNotice::getNewInstance());

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

        // only display the following notice on the Google Pay settings page
        if ('wc-settings' === $page && 'godaddy-payments-google-pay' === $section && ! GooglePayGateway::hasEnabledPages()) {
            Notices::enqueueAdminNotice(GooglePayNoEnabledPagesNotice::getNewInstance());
        }
    }
}
