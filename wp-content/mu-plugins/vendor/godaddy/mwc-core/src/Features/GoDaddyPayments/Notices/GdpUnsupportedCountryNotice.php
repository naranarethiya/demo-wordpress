<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices;

use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\GoDaddyPayments;

class GdpUnsupportedCountryNotice extends Notice
{
    use CanGetNewInstanceTrait;

    /** {@inheritdoc} */
    protected $dismissible = false;

    /** {@inheritdoc} */
    protected $type = self::TYPE_WARNING;

    /** {@inheritdoc} */
    protected $id = 'mwc-payments-godaddy-unsupported-country';

    /**
     * Constructor for GdpUnsupportedCountryNotice notice.
     */
    public function __construct()
    {
        $woocommerce = WooCommerceRepository::getInstance();
        $countries = $woocommerce ? $woocommerce->countries->get_countries() : [];

        /** @var array<string> $supportedCountries */
        $supportedCountries = array_map(
            static fn ($countryCode) => $countries[$countryCode],
            GoDaddyPayments::getSupportedCountries()
        );

        $this->setButtonUrl(esc_url(admin_url('admin.php?page=wc-settings')));
        $this->setButtonText(__('Update Store', 'mwc-core'));
        $this->setContent(sprintf(
            /* translators: %s - list of supported countries */
            __('GoDaddy Payments is available for businesses in %s. Please update your Store Address to use the payment method.', 'mwc-core'),
            ArrayHelper::joinNatural($supportedCountries, 'or'),
        ));
    }
}
