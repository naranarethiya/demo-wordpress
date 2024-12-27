<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices;

use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class BusinessDisconnectedNotice extends Notice
{
    use CanGetNewInstanceTrait;

    /** {@inheritdoc} */
    protected $type = self::TYPE_SUCCESS;

    /** {@inheritdoc} */
    protected $id = 'mwc-payments-godaddy-disconnected';

    /**
     * BusinessDisconnectedNotice constructor.
     */
    public function __construct()
    {
        $this->setTitle(__('Your GoDaddy Payments account has been closed.', 'mwc-core'));
        $this->setContent(__('The payment method has been disabled so it will not appear on your checkout. Please set up your account to resume processing payments.', 'mwc-core'));
    }
}
