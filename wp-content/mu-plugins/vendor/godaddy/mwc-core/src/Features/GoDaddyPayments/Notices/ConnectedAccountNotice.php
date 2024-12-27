<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices;

use Exception;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Producers\OnboardingEventsProducer;

class ConnectedAccountNotice extends Notice
{
    use CanGetNewInstanceTrait;

    /** {@inheritdoc} */
    protected $type = self::TYPE_SUCCESS;

    /** {@inheritdoc} */
    protected $id = 'mwc-payments-godaddy-payments-connection';

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->setTitle(__("You're all set to take payments with GoDaddy Payments!", 'mwc-core'));

        $business = Poynt::getBusiness();
        $message = [];

        if (Onboarding::getRequiredActions()) {
            $message[] = sprintf(
                /* translators: Placeholders: %1$s - <a> tag, %2$s - </a> */
                __('To get your funds deposited to your bank account, verify your identity and add your banking info. %1$sSet up payouts%2$s', 'mwc-core'),
                '<a href="'.esc_url(Onboarding::getApplicationUrl()).'" target="_blank">', '</a>'
            );
        }

        $message[] = sprintf(
            /* translators: Placeholders: %1$s - a connected account's legal name, %2$s - a connected account's email address, %3$s - <a> tag, %4$s - </a> */
            __('The connected GoDaddy Payments account is %1$s %2$s (Not the business owner\'s account? %3$sSwitch account%4$s).', 'mwc-core'),
            $business->getDoingBusinessAs(),
            $business->getEmailAddress(),
            '<a href="'.esc_url(OnboardingEventsProducer::getSwitchStartUrl()).'">',
            '</a>'
        );

        /* @see Notice::getHtml() uses wpautop() */
        $this->setContent(implode("\n\n", $message));
    }
}
