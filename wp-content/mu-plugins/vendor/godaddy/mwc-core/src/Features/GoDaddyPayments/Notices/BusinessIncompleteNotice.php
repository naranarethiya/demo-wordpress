<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices;

use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Producers\OnboardingEventsProducer;

class BusinessIncompleteNotice extends Notice
{
    use CanGetNewInstanceTrait;

    /** {@inheritdoc} */
    protected $dismissible = true;

    /** {@inheritdoc} */
    protected $type = self::TYPE_SUCCESS;

    /** {@inheritdoc} */
    protected $id = 'mwc-payments-godaddy-incomplete';

    public function __construct()
    {
        $this->setTitle(__("It looks like you didn't finish your GoDaddy Payments application.", 'mwc-core'));
        $this->setButtonUrl(OnboardingEventsProducer::getOnboardingStartUrl('admin_notice_resume_link'));
        $this->setButtonText(__('Resume', 'mwc-core'));
        $this->setContent(__("You're just a few minutes from processing payments.", 'mwc-core'));
    }
}
