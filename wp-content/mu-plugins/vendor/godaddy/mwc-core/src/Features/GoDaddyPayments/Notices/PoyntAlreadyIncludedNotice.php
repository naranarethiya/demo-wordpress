<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices;

use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class PoyntAlreadyIncludedNotice extends Notice
{
    use CanGetNewInstanceTrait;

    /** {@inheritdoc} */
    protected $type = self::TYPE_INFO;

    /** {@inheritdoc} */
    protected $id = 'mwc-payments-godaddy-payments-already-included';

    public function __construct()
    {
        $this->setButtonUrl(esc_url(admin_url('admin.php?page=wc-settings&tab=checkout')));
        $this->setButtonText(__('Enable GoDaddy Payments', 'mwc-core'));
        $this->setContent(__('GoDaddy Payments (Poynt) is included for Managed WordPress customers without a separate plugin!', 'mwc-core'));
    }
}
