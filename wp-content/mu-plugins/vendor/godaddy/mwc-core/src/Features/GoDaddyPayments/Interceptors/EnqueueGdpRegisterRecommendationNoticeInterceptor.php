<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notices;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices\GdpRegisterRecommendationNotice;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;

class EnqueueGdpRegisterRecommendationNoticeInterceptor extends AbstractGoDaddyPaymentsRecommendationNoticeInterceptor
{
    /**
     * Determines whether the component should be loaded or not.
     *
     * @throws Exception
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        if ('' !== Onboarding::getStatus()) {
            return false;
        }

        return parent::shouldLoad();
    }

    /* @inheritdoc */
    public function enqueueNotice() : void
    {
        Notices::enqueueAdminNotice(GdpRegisterRecommendationNotice::getNewInstance());
    }
}
