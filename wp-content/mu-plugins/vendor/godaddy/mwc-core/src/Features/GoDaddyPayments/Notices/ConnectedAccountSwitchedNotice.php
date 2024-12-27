<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices;

use Exception;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Producers\OnboardingEventsProducer;

class ConnectedAccountSwitchedNotice extends Notice
{
    use CanGetNewInstanceTrait;

    /** {@inheritdoc} */
    protected $type = self::TYPE_SUCCESS;

    /** {@inheritdoc} */
    protected $id = 'mwc-payments-godaddy-payments-connection-switched';

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $business = Poynt::getBusiness();

        $this->setContent(sprintf(
            /* translators: Placeholders: %1$s - a connected account's legal name, %2$s - a connected account's email address, %3$s - <a> tag, %4$s - </a> */
            __('GoDaddy Payments is now connected with the account %1$s %2$s. (Not right? %3$sSwitch account%4$s.)', 'mwc-core'),
            $business->getDoingBusinessAs(),
            $business->getEmailAddress(),
            '<a href="'.esc_url(OnboardingEventsProducer::getSwitchStartUrl()).'">', '</a>'
        ));
    }
}
