<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices;

use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\GoDaddyPayments;

class GdpUnsupportedCurrencyNotice extends Notice
{
    use CanGetNewInstanceTrait;

    /** {@inheritdoc} */
    protected $dismissible = false;

    /** {@inheritdoc} */
    protected $type = self::TYPE_WARNING;

    /** {@inheritdoc} */
    protected $id = 'mwc-payments-godaddy-unsupported-currency';

    /**
     * Constructor for GdpUnsupportedCurrencyNotice notice.
     */
    public function __construct()
    {
        $currencies = get_woocommerce_currencies();

        /** @var array<string> $supportedCurrencies */
        $supportedCurrencies = array_map(
            static fn ($currencyCode) => $currencies[$currencyCode],
            GoDaddyPayments::getSupportedCurrencies(),
        );

        $this->setButtonUrl(esc_url(admin_url('admin.php?page=wc-settings')));
        $this->setButtonText(__('Change Currency', 'mwc-core'));
        $this->setContent(sprintf(
            /* translators: %s - list of supported currencies */
            __('GoDaddy Payments requires %s transactions. Please change your Currency in order to use the payment method.', 'mwc-core'),
            ArrayHelper::joinNatural($supportedCurrencies, 'or'),
        ));
    }
}
