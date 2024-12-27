<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices;

use Exception;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;

class CompleteVerificationWarningNotice extends Notice
{
    use CanGetNewInstanceTrait;

    /** {@inheritdoc} */
    protected $dismissible = false;

    /** {@inheritdoc} */
    protected $type = self::TYPE_WARNING;

    /** {@inheritdoc} */
    protected $id = 'mwc-payments-godaddy-payments-complete-verification-warning';

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->setButtonUrl(Onboarding::getApplicationUrl());
        $this->setButtonText(__('Complete verification', 'mwc-core'));
        $this->setContent(__('The deadline to complete your payouts setup is coming up! To keep transacting with GoDaddy Payments and to get your money, verify your identity and add banking info.', 'mwc-core'));
    }
}
