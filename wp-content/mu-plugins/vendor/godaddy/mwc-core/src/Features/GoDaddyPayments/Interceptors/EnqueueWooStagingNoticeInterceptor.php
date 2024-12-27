<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notices;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices\WooStagingNotice;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;

class EnqueueWooStagingNoticeInterceptor extends AbstractGoDaddyPaymentsNoticeInterceptor
{
    /**
     * Determines whether the component should be loaded or not.
     *
     * @throws Exception
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        if (! Poynt::isEnabled() || ! Onboarding::canEnablePaymentGateway(Onboarding::getStatus())) {
            return false;
        }

        if (! ManagedWooCommerceRepository::isStagingEnvironment()) {
            return false;
        }

        return parent::shouldLoad();
    }

    /**
     * {@inheritDoc}
     */
    public function enqueueNotice() : void
    {
        Notices::enqueueAdminNotice(WooStagingNotice::getNewInstance());
    }
}
