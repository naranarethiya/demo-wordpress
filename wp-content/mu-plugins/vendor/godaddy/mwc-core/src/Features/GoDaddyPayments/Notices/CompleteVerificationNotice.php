<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices;

use Exception;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;

class CompleteVerificationNotice extends Notice
{
    use CanGetNewInstanceTrait;

    /** {@inheritdoc} */
    protected $type = self::TYPE_SUCCESS;

    /** {@inheritdoc} */
    protected $id = 'mwc-payments-godaddy-payments-complete-profile';

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->setButtonUrl(Onboarding::getApplicationUrl());
        $this->setButtonText(__('Complete verification', 'mwc-core'));
        $this->setContent(__("You've got money waiting with GoDaddy Payments! Verify your identity and add your banking info to get your payouts deposited.", 'mwc-core'));
    }
}
