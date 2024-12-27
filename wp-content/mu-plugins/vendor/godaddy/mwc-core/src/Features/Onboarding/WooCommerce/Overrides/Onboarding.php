<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Onboarding\WooCommerce\Overrides;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;

/**
 * Overrides the behavior of WooCommerce's own onboarding.
 */
class Onboarding extends AbstractInterceptor
{
    /** @var string option key name that WooCommerce uses to decide whether to display the reminder bar */
    const ONBOARDING_TASK_LIST_REMINDER_OPTION_KEY = 'woocommerce_task_list_reminder_bar_hidden';

    /**
     * Adds hooks.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::filter()
            ->setGroup('pre_option_'.static::ONBOARDING_TASK_LIST_REMINDER_OPTION_KEY)
            ->setHandler([$this, 'disableOnboardingReminderBar'])
            ->setPriority(PHP_INT_MIN)
            ->execute();
    }

    /**
     * Disables the onboarding reminder bar.
     *
     * @internal
     *
     * @return string
     */
    public function disableOnboardingReminderBar() : string
    {
        return 'yes';
    }
}
