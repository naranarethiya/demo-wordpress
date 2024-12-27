<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices;

use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class BusinessSuspendedNotice extends Notice
{
    use CanGetNewInstanceTrait;

    /** {@inheritdoc} */
    protected $dismissible = true;

    /** {@inheritdoc} */
    protected $type = self::TYPE_WARNING;

    /** {@inheritdoc} */
    protected $id = 'mwc-payments-godaddy-suspended';

    public function __construct()
    {
        $this->setTitle(__('Your GoDaddy Payments account needs attention.', 'mwc-core'));
        $this->setContent(__('The payment method has been disabled so it will not appear on your checkout. Please check your email for next steps.', 'mwc-core'));
    }
}
