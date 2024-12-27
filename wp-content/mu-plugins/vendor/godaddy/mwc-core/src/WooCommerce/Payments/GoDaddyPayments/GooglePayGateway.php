<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\GoDaddyPayments;
use GoDaddy\WordPress\MWC\Core\Features\Worldpay\Worldpay;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\ExternalCheckout;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\Wallets\GooglePayButton;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPaymentsGateway;

class GooglePayGateway extends AbstractWalletGateway
{
    /** @var string wallet ID */
    protected static string $walletId = 'google-pay';

    /**
     * Google Pay gateway constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->method_title = $this->title = 'GoDaddy Payments - Google Pay';
        $this->method_description = $this->getDescription();

        if (Worldpay::shouldLoad()) {
            $this->method_title = $this->title = 'Google Pay';
            $this->method_description = 'Securely accept card payments and make checkout faster for customers with Google Pay™ on supported devices.';
        }

        parent::__construct();
    }

    /**
     * Gets the method description.
     *
     * @return string
     */
    protected function getDescription() : string
    {
        /* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
        $description = __('Accept Google Pay™ on supported devices and make checkout faster for customers. Get paid fast with deposits as soon as the next business day. %1$sGoDaddy Payments Terms apply%2$s.', 'mwc-core');

        return sprintf(
            $description,
            '<a href="'.GoDaddyPayments::getTermsOfServiceUrl().'" target="_blank">',
            ' <span class="dashicons dashicons-external"></span></a>'
        );
    }

    /**
     * Determines whether the gateway is active.
     *
     * @return bool
     * @throws Exception
     */
    public static function isActive() : bool
    {
        $isGDPConnected = Onboarding::STATUS_CONNECTED === Onboarding::getStatus() && GoDaddyPaymentsGateway::isActive();

        return true === $isGDPConnected
            && ! Worldpay::shouldLoad()
            && Configuration::get('features.google_pay');
    }

    /**
     * Determines whether the Google Pay Gateway has at least one enabled page.
     *
     * @TODO: Abstract this method and reuse it in the Apple Pay gateway notices (MWC-9104) {acastro1 2022-11-07}
     *
     * @return bool
     */
    public static function hasEnabledPages() : bool
    {
        return ! empty(ArrayHelper::wrap(Configuration::get('payments.googlePay.enabledPages', [])));
    }

    /**
     * Initializes the gateway settings form fields.
     *
     * @see WC_Payment_Gateway::init_settings()
     * @see WC_Payment_Gateway::get_form_fields()
     * @see WC_Payment_Gateway::generate_settings_html()
     */
    public function init_form_fields() : void
    {
        $this->form_fields = [
            'godaddy_payments_settings' => [
                'type' => 'parent_gateway_settings',
            ],
            'google_pay_settings' => [
                'type'  => 'title',
                'title' => __('Google Pay Settings', 'mwc-core'),
            ],
            'enabled' => [
                'title'       => __('Enable', 'mwc-core'),
                'label'       => __('Enable to add the payment method to your checkout.', 'mwc-core'),
                'description' => sprintf(
                    /* translators: Placeholders: %1$s - open <a> HTML link tag, %2$s - close </a> HTML link tag */
                    __('Google Pay shows on %1$ssupported browsers%2$s.', 'mwc-core'),
                    '<a href="https://developers.google.com/pay/api/web/guides/setup" target="_blank">',
                    ' <span class="dashicons dashicons-external"></span></a>'
                ),
                'type'    => 'checkbox',
                'default' => 'no',
            ],
            'enabled_pages' => [
                'title'   => __('Pages to enable Google Pay on', 'mwc-core'),
                'type'    => 'multiselect',
                'class'   => 'wc-enhanced-select',
                'default' => [
                    ExternalCheckout::BUTTON_PAGE_CART,
                    ExternalCheckout::BUTTON_PAGE_CHECKOUT,
                ],
                'options' => [
                    ExternalCheckout::BUTTON_PAGE_CART           => __('Cart', 'mwc-core'),
                    ExternalCheckout::BUTTON_PAGE_CHECKOUT       => __('Checkout', 'mwc-core'),
                    ExternalCheckout::BUTTON_PAGE_SINGLE_PRODUCT => __('Single Product', 'mwc-core'),
                ],
            ],
            /* @link https://developers.google.com/pay/api/web/guides/brand-guidelines#style */
            'button_type' => [
                'title'       => __('Button label', 'mwc-core'),
                'description' => '<a href="https://developers.google.com/pay/api/web/guides/brand-guidelines#style" target="_blank">'.__('Check button labels here', 'mwc-core').' <span class="dashicons dashicons-external"></span></a>',
                'type'        => 'select',
                'class'       => 'wc-enhanced-select',
                'default'     => GooglePayButton::BUTTON_TYPE_BUY,
                'options'     => [
                    GooglePayButton::BUTTON_TYPE_BOOK     => _x('Book with', 'Google Pay', 'mwc-core'),
                    GooglePayButton::BUTTON_TYPE_BUY      => _x('Buy with', 'Google Pay', 'mwc-core'),
                    GooglePayButton::BUTTON_TYPE_CHECKOUT => _x('Check out with', 'Google Pay', 'mwc-core'),
                    GooglePayButton::BUTTON_TYPE_DONATE   => _x('Donate with', 'Google Pay', 'mwc-core'),
                    GooglePayButton::BUTTON_TYPE_ORDER    => _x('Order with', 'Google Pay', 'mwc-core'),
                    GooglePayButton::BUTTON_TYPE_PAY      => _x('Pay with', 'Google Pay', 'mwc-core'),
                    GooglePayButton::BUTTON_TYPE_PLAIN    => _x('Plain (logo only)', 'Google Pay', 'mwc-core'),
                ],
            ],
            /* @link https://developers.google.com/pay/api/web/guides/brand-guidelines#style */
            'button_style' => [
                'title'       => __('Button style', 'mwc-core'),
                'description' => '<a href="https://developers.google.com/pay/api/web/guides/brand-guidelines#style" target="_blank">'.__('Check button style here', 'mwc-core').' <span class="dashicons dashicons-external"></span></a>',
                'type'        => 'select',
                'class'       => 'wc-enhanced-select',
                'default'     => 'BLACK',
                'options'     => [
                    GooglePayButton::BUTTON_STYLE_DEFAULT => __('Default', 'mwc-core'),
                    GooglePayButton::BUTTON_STYLE_BLACK   => __('Black', 'mwc-core'),
                    GooglePayButton::BUTTON_STYLE_WHITE   => __('White', 'mwc-core'),
                ],
            ],
            /* @link https://developers.google.com/pay/api/web/guides/brand-guidelines#style */
            'button_height' => [
                'type'              => 'number',
                'title'             => __('Button height', 'mwc-core'),
                'description'       => __('Google requests the button size match your cart/checkout button and be 40 to 100 pixels tall.', 'mwc-core').'<br><a href="https://developers.google.com/pay/api/web/guides/brand-guidelines#style" target="_blank">'.__('Check button size here', 'mwc-core').' <span class="dashicons dashicons-external"></span></a>',
                'css'               => 'max-width: 105px',
                'default'           => GooglePayButton::BUTTON_HEIGHT_DEFAULT,
                'custom_attributes' => [
                    'step' => 1,
                    'min'  => GooglePayButton::BUTTON_HEIGHT_MIN,
                    'max'  => GooglePayButton::BUTTON_HEIGHT_MAX,
                ],
            ],
        ];
    }

    /**
     * Checks whether the payment gateway should be auto enabled.
     *
     * @return bool
     */
    public function shouldAutoEnable() : bool
    {
        return true;
    }
}
