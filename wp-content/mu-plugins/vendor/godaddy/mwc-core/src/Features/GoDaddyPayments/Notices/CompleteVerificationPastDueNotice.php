<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices;

use Exception;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;

class CompleteVerificationPastDueNotice extends Notice
{
    use CanGetNewInstanceTrait;

    /** {@inheritdoc} */
    protected $dismissible = false;

    /** {@inheritdoc} */
    protected $type = self::TYPE_ERROR;

    /** {@inheritdoc} */
    protected $id = 'mwc-payments-godaddy-payments-complete-profile';

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->setButtonUrl(Onboarding::getApplicationUrl());
        $this->setButtonText(__('Complete verification', 'mwc-core'));
        $this->setContent(__("You haven't completed the necessary steps to verify your identity with GoDaddy Payments. Until you do that, you won't be able to take any payments or deposit your funds.", 'mwc-core'));
    }
}
