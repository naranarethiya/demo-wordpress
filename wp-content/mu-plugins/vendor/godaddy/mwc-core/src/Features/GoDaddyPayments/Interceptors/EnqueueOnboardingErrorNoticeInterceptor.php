<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notices;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices\OnboardingErrorNotice;
use GoDaddy\WordPress\MWC\Core\Features\Worldpay\Worldpay;

class EnqueueOnboardingErrorNoticeInterceptor extends AbstractGoDaddyPaymentsNoticeInterceptor
{
    /**
     * Determines whether the component should be loaded or not.
     *
     * @throws Exception
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        if (Worldpay::shouldLoad()) {
            return false;
        }

        if (! ArrayHelper::get($_GET, 'onboardingError')) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function enqueueNotice() : void
    {
        Notices::enqueueAdminNotice(OnboardingErrorNotice::getNewInstance());
    }
}
