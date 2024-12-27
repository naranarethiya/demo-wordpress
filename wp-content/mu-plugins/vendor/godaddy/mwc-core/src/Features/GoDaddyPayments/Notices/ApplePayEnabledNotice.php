<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices;

use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class ApplePayEnabledNotice extends Notice
{
    use CanGetNewInstanceTrait;

    /** {@inheritdoc} */
    protected $type = self::TYPE_SUCCESS;

    /** {@inheritdoc} */
    protected $id = 'mwc-payments-godaddy-payments-apple-pay-enabled';

    public function __construct()
    {
        $this->setContent(sprintf(
            __('GoDaddy Payments - Apple Pay has been enabled on your selected pages and shows %1$sin Safari on supported devices%2$s.', 'mwc-core'),
            '<a href="https://support.apple.com/en-us/HT208531" target="_blank">',
            ' <span class="dashicons dashicons-external"></span></a>'
        ));
    }
}
