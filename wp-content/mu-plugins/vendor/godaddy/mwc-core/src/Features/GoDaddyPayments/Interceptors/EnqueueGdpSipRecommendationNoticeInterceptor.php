<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notices;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices\GdpSipRecommendationNotice;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;

class EnqueueGdpSipRecommendationNoticeInterceptor extends AbstractGoDaddyPaymentsRecommendationNoticeInterceptor
{
    /**
     * Determines whether the component should be loaded or not.
     *
     * @throws Exception
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        if (static::isSiPGatewayEnabled() || ! Onboarding::canEnablePaymentGateway(Onboarding::getStatus())) {
            return false;
        }

        return parent::shouldLoad();
    }

    /**
     * {@inheritDoc}
     */
    public function enqueueNotice() : void
    {
        Notices::enqueueAdminNotice(GdpSipRecommendationNotice::getNewInstance());
    }

    /**
     * Determines whether the GoDaddy Payments Sell in Person gateway is enabled.
     *
     * @return bool
     */
    protected static function isSiPGatewayEnabled() : bool
    {
        return TypeHelper::bool(Configuration::get('payments.godaddy-payments-payinperson.enabled'), false);
    }
}
