<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices;

use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class BusinessTerminatedNotice extends Notice
{
    use CanGetNewInstanceTrait;

    /** {@inheritdoc} */
    protected $type = self::TYPE_ERROR;

    /** {@inheritdoc} */
    protected $id = 'mwc-payments-godaddy-terminated';

    /**
     * BusinessTerminatedNotice constructor.
     */
    public function __construct()
    {
        $this->setTitle(__('Your GoDaddy Payments account has been terminated.', 'mwc-core'));
        $this->setContent(__('The payment method has been disabled so it will not appear on your checkout. Please check your email for more information.', 'mwc-core'));
    }
}
