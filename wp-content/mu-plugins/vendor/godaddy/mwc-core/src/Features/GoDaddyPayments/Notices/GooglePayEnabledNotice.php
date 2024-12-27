<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices;

use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class GooglePayEnabledNotice extends Notice
{
    use CanGetNewInstanceTrait;

    /** {@inheritdoc} */
    protected $type = self::TYPE_SUCCESS;

    /** {@inheritdoc} */
    protected $id = 'mwc-payments-godaddy-payments-google-pay-enabled';

    public function __construct()
    {
        $this->setContent(sprintf(
            /* translators: Placeholders: %1$s - <a> tag for the Google Pay docs link, %2$s - </a> tag */
            __('GoDaddy Payments - Google Pay has been enabled on your selected pages and shows %1$sin supported browsers and devices%2$s.', 'mwc-core'),
            '<a href="https://developers.google.com/pay/api/web/guides/test-and-deploy/integration-checklist#test-using-browser-developer-console" target="_blank">',
            ' <span class="dashicons dashicons-external"></span></a>'
        ));
    }
}
