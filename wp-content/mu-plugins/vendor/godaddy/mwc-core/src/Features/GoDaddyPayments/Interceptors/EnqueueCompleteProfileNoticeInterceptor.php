<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notices;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices\AddBankInfoNotice;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices\CompleteVerificationNotice;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices\CompleteVerificationPastDueNotice;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices\CompleteVerificationReminderNotice;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices\CompleteVerificationWarningNotice;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;

class EnqueueCompleteProfileNoticeInterceptor extends AbstractGoDaddyPaymentsNoticeInterceptor
{
    /** @var array<string, class-string<Notice>> list of notice classes corresponding to a required action (order is significant) */
    protected array $noticeClasses = [
        Onboarding::ACTION_COMPLETE_VERIFICATION_PAST_DUE => CompleteVerificationPastDueNotice::class,
        Onboarding::ACTION_COMPLETE_VERIFICATION_WARNING  => CompleteVerificationWarningNotice::class,
        Onboarding::ACTION_COMPLETE_VERIFICATION_REMINDER => CompleteVerificationReminderNotice::class,
        Onboarding::ACTION_COMPLETE_VERIFICATION          => CompleteVerificationNotice::class,
        Onboarding::ACTION_ADD_BANK                       => AddBankInfoNotice::class,
    ];

    /**
     * Determines whether the component should be loaded or not.
     *
     * @throws Exception
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        if (true !== Configuration::get('features.gdp_by_default.enabled')) {
            return false;
        }

        if (Onboarding::STATUS_TERMINATED === Onboarding::getStatus() || ! Configuration::get('payments.poynt.onboarding.hasFirstPayment', false)) {
            return false;
        }

        return parent::shouldLoad();
    }

    /* @inheritdoc */
    public function enqueueNotice() : void
    {
        $requiredActions = Onboarding::getRequiredActions();

        foreach ($this->noticeClasses as $requiredAction => $noticeClass) {
            if (ArrayHelper::contains($requiredActions, $requiredAction)) {
                /* @var $noticeClass Notice */
                Notices::enqueueAdminNotice($noticeClass::getNewInstance());

                // prevent enqueueing any other notices
                break;
            }
        }
    }
}
