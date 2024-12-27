<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\Frontend;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use WC_Customer;
use WC_Order;

class PaymentForm extends \GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\PaymentForm
{
    /**
     * Registers the hooks.
     *
     * @throws Exception
     */
    protected function registerHooks() : void
    {
        if (! Poynt::isEnabled()) {
            return;
        }

        parent::registerHooks();

        Register::action()
            ->setGroup('wp_enqueue_scripts')
            ->setHandler([$this, 'enqueueScripts'])
            ->execute();
    }

    /**
     * Enqueues the scripts.
     *
     * @internal
     *
     * @throws Exception
     */
    public function enqueueScripts() : void
    {
        $sdkUrl = ManagedWooCommerceRepository::isProductionEnvironment() ? Configuration::get('payments.poynt.api.productionSdkUrl') : Configuration::get('payments.poynt.api.stagingSdkUrl');

        Enqueue::script()
            ->setHandle('poynt-collect-sdk')
            ->setSource($sdkUrl)
            ->execute();

        Enqueue::script()
            ->setHandle('mwc-payments-poynt-payment-form')
            ->setSource(WordPressRepository::getAssetsUrl('js/payments/frontend/poynt.js'))
            ->setDependencies(['jquery', 'poynt-collect-sdk'])
            ->attachInlineScriptObject('poyntPaymentFormI18n')
            ->attachInlineScriptVariables([
                'errorMessages' => [
                    'genericError'          => __('An error occurred, please try again or try an alternate form of payment.', 'mwc-core'),
                    'missingCardDetails'    => __('Missing card details.', 'mwc-core'),
                    'missingBillingDetails' => __('Missing billing details.', 'mwc-core'),
                ],
            ])
            ->execute();

        wc_enqueue_js(sprintf(
            'window.mwc_payments_poynt_payment_form_handler = new MWCPaymentsPoyntPaymentFormHandler(%s);',
            ArrayHelper::jsonEncode([
                'appId'            => Poynt::getAppId(),
                'businessId'       => Poynt::getBusinessId(),
                'customerAddress'  => $this->getCustomerAddress(),
                'shippingAddress'  => $this->getShippingAddress(),
                'isLoggingEnabled' => Configuration::get('mwc.debug'),
                'options'          => $this->getOptions(),
            ])
        ));
    }

    /**
     * Gets the Poynt Collect options.
     *
     * For configuration options, see:
     * @link https://docs.poynt.com/app-integration/poynt-collect/getting-started.html#collect-mount
     *
     * For CSS options, see:
     * @link https://docs.poynt.com/app-integration/poynt-collect/advanced-topics.html#customized-css
     * @link https://github.com/medipass/react-payment-inputs#styles
     *
     * @return array<string, mixed>
     */
    protected function getOptions() : array
    {
        $showZip = $this->shouldShowZipField();

        /*
         * Filters the Poynt Collect JS options.
         *
         * @param array{
         *     iFrame?: array<string, mixed>,
         *     style?:  array<string, mixed>,
         *     displayComponents?: array<string, mixed>,
         *     fields?: array<string, mixed>,
         *     additionalFieldsToValidate?: string[],
         *     customCss?: array<string, mixed>,
         * } $options
         */
        return TypeHelper::array(apply_filters('mwc_payments_poynt_collect_options', [
            'iFrame' => [
                'border'       => '0px',
                'borderRadius' => '0px',
                'boxShadow'    => 'none',
                'height'       => $showZip ? '400px' : '320px',
                'width'        => 'auto',
            ],
            'style' => [
                'theme' => 'checkout',
            ],
            'displayComponents' => [
                'firstName'    => false,
                'lastName'     => false,
                'emailAddress' => false,
                'zipCode'      => $showZip,
                'labels'       => true,
                'submitButton' => false,
            ],
            'customCss' => [
                'container' => [
                    'margin-top' => '10px',
                ],
                'inputLabel' => [
                    'font-size' => '16px',
                ],
            ],
            'enableReCaptcha'  => true,
            'reCaptchaOptions' => [
                'type' => 'TEXT',
            ],
        ]), []);
    }

    /**
     * Renders the payment fields.
     */
    protected function renderPaymentFields() : void
    {
        parent::renderPaymentFields();

        $nonceFieldId = 'mwc-payments-'.$this->providerName.'-payment-nonce'; ?>
        <div id="mwc-payments-poynt-hosted-form"></div>
        <input type="hidden" id="<?php echo esc_attr($nonceFieldId); ?>" name="<?php echo esc_attr($nonceFieldId); ?>">
        <?php
    }

    /**
     * Gets the current customer's address.
     *
     * @return array<string, mixed>
     */
    protected function getCustomerAddress() : array
    {
        $address = [
            'firstName' => '',
            'lastName'  => '',
            'phone'     => '',
            'email'     => '',
            'line1'     => '',
            'line2'     => '',
            'city'      => '',
            'state'     => '',
            'country'   => '',
            'postcode'  => '',
        ];

        // if on the checkout pay page use the order's address details
        if ($order = $this->getPendingPayPageOrder()) {
            $address['firstName'] = $order->get_billing_first_name();
            $address['lastName'] = $order->get_billing_last_name();
            $address['phone'] = $order->get_billing_phone();
            $address['email'] = $order->get_billing_email();
            $address['line1'] = $order->get_billing_address_1();
            $address['line2'] = $order->get_billing_address_2();
            $address['city'] = $order->get_billing_city();
            $address['state'] = $order->get_billing_state();
            $address['country'] = $order->get_billing_country();
            $address['postcode'] = $order->get_billing_postcode();

            return $address;
        }

        // get the current customer's address details if available
        if (WC()->customer instanceof WC_Customer) {
            $address['firstName'] = WC()->customer->get_billing_first_name();
            $address['lastName'] = WC()->customer->get_billing_last_name();
            $address['phone'] = WC()->customer->get_billing_phone();
            $address['email'] = WC()->customer->get_billing_email();
            $address['line1'] = WC()->customer->get_billing_address_1();
            $address['line2'] = WC()->customer->get_billing_address_2();
            $address['city'] = WC()->customer->get_billing_city();
            $address['state'] = WC()->customer->get_billing_state();
            $address['country'] = WC()->customer->get_billing_country();
            $address['postcode'] = WC()->customer->get_billing_postcode();
        }

        return $address;
    }

    /**
     * Gets the current shipping address.
     *
     * @return array<string, mixed>
     */
    protected function getShippingAddress() : array
    {
        $address = [
            'line1'         => '',
            'line2'         => '',
            'city'          => '',
            'state'         => '',
            'country'       => '',
            'postcode'      => '',
            'needsShipping' => false,
        ];

        // if on the checkout pay page use the order's address details
        if ($order = $this->getPendingPayPageOrder()) {
            $address['line1'] = $order->get_shipping_address_1();
            $address['line2'] = $order->get_shipping_address_2();
            $address['city'] = $order->get_shipping_city();
            $address['state'] = $order->get_shipping_state();
            $address['country'] = $order->get_shipping_country();
            $address['postcode'] = $order->get_shipping_postcode();
            $address['needsShipping'] = $order->needs_shipping_address();

            return $address;
        } elseif (WC()->cart && WC()->cart->needs_shipping()) {
            $address['needsShipping'] = true;
        }

        return $address;
    }

    /**
     * Gets the checkout pay page order if available.
     *
     * @return WC_Order|null
     */
    protected function getPendingPayPageOrder() : ?WC_Order
    {
        return WooCommerceRepository::isCheckoutPayPage()
        && ($order = OrdersRepository::get($this->getOrderPayQueryVar()))
        && $order instanceof WC_Order
            ? $order
            : null;
    }

    /**
     * Determines whether the payment form should show the ZIP (postcode) field.
     *
     * @return bool
     */
    protected function shouldShowZipField() : bool
    {
        return ! ArrayHelper::get($this->getCustomerAddress(), 'postcode') && ! $this->hasCheckoutPostcodeField();
    }

    /**
     * Determines whether checkout has the postcode field.
     *
     * @return bool
     */
    protected function hasCheckoutPostcodeField() : bool
    {
        $wc = WooCommerceRepository::getInstance();

        return ArrayHelper::has($wc ? $wc->checkout()->get_checkout_fields() : [], 'billing.billing_postcode');
    }

    /**
     * Gets order pay query var from request.
     *
     * @return int
     */
    protected function getOrderPayQueryVar() : int
    {
        global $wp;

        return (int) ($wp->query_vars['order-pay'] ?? 0);
    }
}
