<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Interceptors;

use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notices;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices\BusinessConnectedNotice;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices\BusinessDisconnectedNotice;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices\BusinessIncompleteNotice;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices\BusinessSuspendedNotice;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices\BusinessTerminatedNotice;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;

/**
 * The business status notice interceptor.
 */
class EnqueueBusinessStatusNoticeInterceptor extends AbstractGoDaddyPaymentsNoticeInterceptor
{
    /** @var array<string, class-string<Notice>> list of notice classes corresponding to onboarding status (order does not matter) */
    protected array $noticeClasses = [
        Onboarding::STATUS_CONNECTED    => BusinessConnectedNotice::class,
        Onboarding::STATUS_DISCONNECTED => BusinessDisconnectedNotice::class,
        Onboarding::STATUS_INCOMPLETE   => BusinessIncompleteNotice::class,
        Onboarding::STATUS_SUSPENDED    => BusinessSuspendedNotice::class,
        Onboarding::STATUS_TERMINATED   => BusinessTerminatedNotice::class,
    ];

    /**
     * {@inheritDoc}
     */
    public function enqueueNotice() : void
    {
        if ($noticeClass = $this->noticeClasses[Onboarding::getStatus()] ?? null) {
            /* @var $noticeClass Notice */
            Notices::enqueueAdminNotice($noticeClass::getNewInstance());
        }
    }
}
