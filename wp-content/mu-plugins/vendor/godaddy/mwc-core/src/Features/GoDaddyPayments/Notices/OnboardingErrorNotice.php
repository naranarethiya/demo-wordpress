<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices;

use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class OnboardingErrorNotice extends Notice
{
    use CanGetNewInstanceTrait;

    /** {@inheritdoc} */
    protected $dismissible = false;

    /** {@inheritdoc} */
    protected $type = self::TYPE_ERROR;

    /** {@inheritdoc} */
    protected $id = 'mwc-payments-godaddy-onboarding-error';

    /**
     * OnboardingErrorNotice constructor.
     */
    public function __construct()
    {
        $this->setContent(__('There was an error connecting to GoDaddy Payments. Please try again.', 'mwc-core'));
    }
}
