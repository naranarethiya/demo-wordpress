<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notices;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\GoDaddyPayments;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices\GdpUnsupportedCountryNotice;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices\GdpUnsupportedCurrencyForCurrentCountryNotice;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices\GdpUnsupportedCurrencyNotice;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;

class EnqueueGdpNoticeInterceptor extends AbstractGoDaddyPaymentsNoticeInterceptor
{
    /**
     * Determines whether the component should be loaded or not.
     *
     * @throws Exception
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        if (! WooCommerceRepository::isWooCommerceActive() ||
            ! Poynt::isEnabled() ||
            ! Onboarding::canEnablePaymentGateway(Onboarding::getStatus())
        ) {
            return false;
        }

        return parent::shouldLoad();
    }

    /**
     * {@inheritDoc}
     */
    public function enqueueNotice() : void
    {
        $country = WooCommerceRepository::getBaseCountry();
        $currency = WooCommerceRepository::getCurrency();

        $isSupportedCountry = GoDaddyPayments::isSupportedCountry($country);

        // if country is supported, only check if the currency is supported for the given country
        if ($isSupportedCountry && ! GoDaddyPayments::isSupportedCurrency($currency, $country)) {
            Notices::enqueueAdminNotice(GdpUnsupportedCurrencyForCurrentCountryNotice::getNewInstance());

            return;
        }

        if (! $isSupportedCountry) {
            Notices::enqueueAdminNotice(GdpUnsupportedCountryNotice::getNewInstance());
        }

        if (! GoDaddyPayments::isSupportedCurrency($currency)) {
            Notices::enqueueAdminNotice(GdpUnsupportedCurrencyNotice::getNewInstance());
        }
    }
}
