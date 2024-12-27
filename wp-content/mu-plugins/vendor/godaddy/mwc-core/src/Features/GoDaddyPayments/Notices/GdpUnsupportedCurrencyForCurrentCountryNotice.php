<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices;

use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\GoDaddyPayments;

class GdpUnsupportedCurrencyForCurrentCountryNotice extends Notice
{
    use CanGetNewInstanceTrait;

    /** {@inheritdoc} */
    protected $dismissible = false;

    /** {@inheritdoc} */
    protected $type = self::TYPE_WARNING;

    /** {@inheritdoc} */
    protected $id = 'mwc-payments-godaddy-unsupported-currency-for-current-country';

    /**
     * Constructor for GdpUnsupportedCurrencyForCurrentCountryNotice notice.
     */
    public function __construct()
    {
        $baseCountry = WooCommerceRepository::getBaseCountry();
        $woocommerce = WooCommerceRepository::getInstance();
        $countries = $woocommerce ? $woocommerce->countries->get_countries() : [];
        $currencies = get_woocommerce_currencies();

        /** @var array<string> $supportedCurrencies */
        $supportedCurrencies = array_map(
            static fn ($currencyCode) => $currencies[$currencyCode],
            GoDaddyPayments::getSupportedCurrencies($baseCountry),
        );

        $this->setButtonUrl(esc_url(admin_url('admin.php?page=wc-settings')));
        $this->setButtonText(__('Change Currency', 'mwc-core'));
        $this->setContent(sprintf(
            /* translators: %1$s - list of supported currencies, %2$s - store country */
            __('GoDaddy Payments requires %1$s transactions for businesses in %2$s. Please change your Currency in order to use the payment method.', 'mwc-core'),
            ArrayHelper::joinNatural($supportedCurrencies, 'or'),
            TypeHelper::string(ArrayHelper::get($countries, $baseCountry, $baseCountry), ''),
        ));
    }
}
