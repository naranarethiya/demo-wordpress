<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices;

use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class BusinessEnabledNotice extends Notice
{
    use CanGetNewInstanceTrait;

    /** {@inheritdoc} */
    protected $dismissible = true;

    /** {@inheritdoc} */
    protected $type = self::TYPE_SUCCESS;

    /** {@inheritdoc} */
    protected $id = 'mwc-payments-godaddy-connected';

    public function __construct()
    {
        $this->setTitle(__('GoDaddy Payments successfully enabled!', 'mwc-core'));
        $this->setContent(__('GoDaddy Payments is now available to your customers at checkout.', 'mwc-core'));
    }
}
