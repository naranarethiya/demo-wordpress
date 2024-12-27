<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices;

use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class ApplePayRegistrationFailedNotice extends Notice
{
    use CanGetNewInstanceTrait;

    /** {@inheritdoc} */
    protected $dismissible = false;

    /** {@inheritdoc} */
    protected $type = self::TYPE_ERROR;

    /** {@inheritdoc} */
    protected $id = 'mwc-payments-godaddy-payments-apple-pay-registration-failed';

    public function __construct()
    {
        $this->setContent(sprintf(
            __('There was a problem registering your site with Apple Pay. Please disable Apple Pay and try re-enabling, or %1$scontact support%2$s.', 'mwc-core'),
            '<a href="'.esc_url(admin_url('admin.php?page=godaddy-get-help')).'">',
            '</a>'
        ));
    }
}
