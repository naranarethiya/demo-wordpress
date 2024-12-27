<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Stripe\Frontend;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\Address;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\CartRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\CustomerDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\OrderPaymentTransactionDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Transactions\PaymentTransaction;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Adapters\CartPaymentIntentAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Adapters\TransactionPaymentIntentAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\DataStores\WooCommerce\SessionPaymentIntentDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Gateways\CustomersGateway;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Gateways\PaymentIntentGateway;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Gateways\SetupIntentGateway;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Models\PaymentIntent;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Models\SetupIntent;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\OrderAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Exceptions\MissingOrderException;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\CorePaymentGateways;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\PaymentForm as WooCommercePaymentForm;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\StripeGateway;
use GoDaddy\WordPress\MWC\Payments\DataSources\WooCommerce\Adapters\CustomerAdapter;
use GoDaddy\WordPress\MWC\Payments\Models\Customer;
use Stripe\Stripe as StripeSDK;
use WC_Customer;
use WC_Order;

/**
 * The payment form class.
 *
 * Used for rendering the payment form for the native Stripe integration.
 */
class PaymentForm extends WooCommercePaymentForm
{
    /**
     * Registers the action & filter hooks.
     *
     * @throws Exception
     */
    public function registerHooks()
    {
        if (! $this->shouldRegisterHooks()) {
            return;
        }

        parent::registerHooks();

        Register::action()
                ->setGroup('wp_enqueue_scripts')
                ->setHandler([$this, 'enqueueScripts'])
                ->execute();
    }

    /**
     * Determines whether the hooks for Stripe should be registered or not.
     *
     * @NOTE: In its implementation, when the payments.stripe.enabled configuration is true, it means
     *        Stripe::isConnected() is also true. The configuration is checked before the static method for
     *        optimization purposes given that it's cheaper. At the same time, the static method is kept
     *        to cover cases when the enabled configuration is set to true but the gateway is not connected or
     *        even when it's still cached.
     *
     * @return bool
     */
    protected function shouldRegisterHooks() : bool
    {
        return Configuration::get('payments.stripe.enabled', false) && Stripe::isConnected();
    }

    /**
     * Enqueues the required scripts.
     *
     * @throws Exception
     */
    public function enqueueScripts()
    {
        Enqueue::script()
               ->setHandle('stripe-payment')
               ->setSource('https://js.stripe.com/v3/')
               ->execute();

        Enqueue::script()
               ->setHandle('mwc-payments-stripe-payment-form')
               ->setSource(WordPressRepository::getAssetsUrl('js/payments/frontend/stripe.js'))
               ->setDependencies(['jquery', 'stripe-payment'])
               ->execute();

        $payFormArgs = [
            'appInfo'                    => StripeSDK::getAppInfo(),
            'publishableKey'             => Stripe::getApiPublicKey(),
            'appearanceOptions'          => $this->getAppearanceOptions(),
            'billingDetails'             => $this->getBillingDetails(),
            'isLoggingEnabled'           => Configuration::get('mwc.debug'),
            'redirectUrl'                => $this->getRedirectUrl(),
            'genericError'               => __('An error occurred, please try again or try an alternate form of payment.', 'mwc-core'),
            'isDetailedDecline'          => Configuration::get('payments.stripe.detailedDecline'),
            'reusablePaymentMethodTypes' => Stripe::getReusablePaymentMethodTypes(),
        ];

        wc_enqueue_js(sprintf(
            'window.mwc_payments_stripe_payment_form_handler = new MWCPaymentsStripePaymentFormHandler(%s);',
            ArrayHelper::jsonEncode($payFormArgs)
        ));
    }

    /**
     * Renders the payment fields.
     */
    protected function renderPaymentFields()
    {
        parent::renderPaymentFields();

        try {
            $clientSecret = $this->getClientSecret(); ?>
            <div id="mwc-payments-stripe-form"></div>
            <input type="hidden" id="mwc-payments-stripe-client-secret" name="mwc-payments-stripe-client-secret" value="<?php echo esc_attr($clientSecret); ?>">
        <?php
        } catch (Exception $exception) {
            $message = $exception->getMessage();

            new SentryException($message, $exception);

            if (ArrayHelper::contains([
                StripeGateway::DEBUG_MODE_BOTH,
                StripeGateway::DEBUG_MODE_CHECKOUT,
            ], Configuration::get('payments.stripe.debugMode'))) {
                ?>
                <div class="woocommerce-error"><?php echo esc_html($message); ?></div>
                <?php
            } else {
                ?>
                <div class="woocommerce-info"><?php esc_html_e('Currently unavailable', 'mwc-core'); ?></div>
                <?php
            }
        }
    }

    /**
     * Returns a payment transaction.
     *
     * @throws Exception
     */
    protected function getTransaction() : PaymentTransaction
    {
        return CorePaymentGateways::getManagedPaymentGatewayInstance('stripe')->getTransactionForPayment($this->getOrderFromQueryVar());
    }

    /**
     * Gets appearance options for the Stripe payment form.
     *
     * @link https://stripe.com/docs/elements/appearance-api
     *
     * @return array
     */
    public function getAppearanceOptions() : array
    {
        /*
         * Filters the Stripe payment form appearance options.
         *
         * @param array $options
         */
        return apply_filters('mwc_payments_stripe_form_appearance_options', []);
    }

    /**
     * Gets the client secret from the Payment Intent.
     *
     * @throws Exception
     */
    protected function getClientSecret() : string
    {
        if (WooCommerceRepository::isCheckoutPayPage()) {
            return $this->getOrderClientSecret();
        } elseif (is_add_payment_method_page()) {
            return $this->getSetupClientSecret();
        }

        return $this->getSessionClientSecret();
    }

    /**
     * Gets the client secret for payment setup.
     *
     * @return string
     * @throws Exception
     */
    protected function getSetupClientSecret() : string
    {
        $setupIntent = SetupIntent::getNewInstance();

        if ($currentUserId = get_current_user_id()) {
            $customer = CustomerAdapter::getNewInstance(new WC_Customer($currentUserId))->convertFromSource();

            if (! $customer->getRemoteId()) {
                $customer = CustomersGateway::getNewInstance()->create($customer);
                $customer = CustomerDataStore::getNewInstance($this->providerName)->save($customer);
            }

            $setupIntent->setCustomer($customer);
        }

        $setupIntent = SetupIntentGateway::getNewInstance()->create($setupIntent);

        return $setupIntent->getClientSecret() ?? '';
    }

    /**
     * Gets the client secret for the current session's payment intent.
     *
     * If the session doesn't have an intent, one is created.
     *
     * @return string
     * @throws Exception
     */
    protected function getSessionClientSecret() : string
    {
        $dataStore = SessionPaymentIntentDataStore::getNewInstance();

        $paymentIntent = CartPaymentIntentAdapter::getNewInstance(CartRepository::getInstance())->convertFromSource($dataStore->read());

        // switch to a setup intent if this is a $0 total
        if (! $paymentIntent->getAmount()) {
            return $this->getSetupClientSecret();
        }

        if ($this->shouldSetUpOffSession()) {
            $paymentIntent->setSetupFutureUsage('off_session');
        }

        $paymentIntent = PaymentIntentGateway::getNewInstance()->upsert($paymentIntent);

        $dataStore->save($paymentIntent);

        return $paymentIntent->getClientSecret();
    }

    /**
     * Gets the client secret for the current order form.
     *
     * @return string
     * @throws Exception
     */
    protected function getOrderClientSecret() : string
    {
        $currentTransaction = $this->getTransaction();

        if (static::isSetupTransaction($currentTransaction)) {
            return $this->getSetupClientSecret();
        }

        $paymentIntent = TransactionPaymentIntentAdapter::getNewInstance($currentTransaction)->convertFromSource($this->getExistingTransactionIntent($currentTransaction));

        if ($this->shouldSetUpOffSession()) {
            $paymentIntent->setSetupFutureUsage('off_session');
        }

        $paymentIntent = PaymentIntentGateway::getNewInstance()->upsert($paymentIntent);

        $currentTransaction->setRemoteId($paymentIntent->getId());

        OrderPaymentTransactionDataStore::getNewInstance($this->providerName)->save($currentTransaction);

        return $paymentIntent->getClientSecret();
    }

    /**
     * Gets an existing intent for the current transaction, if any.
     *
     * @param PaymentTransaction $transaction
     *
     * @return PaymentIntent|null
     */
    protected function getExistingTransactionIntent(PaymentTransaction $transaction) : ?PaymentIntent
    {
        try {
            // sanity check for the order
            if (! $order = $transaction->getOrder()) {
                return null;
            }

            // nothing more to do if there is no ID stored
            if (! $existingId = OrderPaymentTransactionDataStore::getNewInstance($this->providerName)->read((int) $order->getId(), 'payment')->getRemoteId()) {
                return null;
            }

            return PaymentIntentGateway::getNewInstance()->get($existingId);
        } catch (Exception $exception) {
        }

        return null;
    }

    /**
     * Returns the order of the id in the query var.
     *
     * @return WC_Order
     * @throws MissingOrderException
     */
    protected function getOrderFromQueryVar() : WC_Order
    {
        global $wp;

        if (! $orderId = (int) $wp->query_vars['order-pay']) {
            throw new MissingOrderException('Order ID is missing');
        }

        return OrdersRepository::get($orderId);
    }

    /**
     * Determines whether the payment intent should be set up for off-session payments.
     *
     * @return bool
     */
    protected function shouldSetUpOffSession() : bool
    {
        return $this->forceTokenization();
    }

    /**
     * Generates a redirect URL that will be used by the JS form handler for after the payment completes.
     *
     * @param PaymentTransaction $paymentTransaction
     *
     * @return string
     * @throws MissingOrderException
     */
    public static function getPaymentRedirectUrl(PaymentTransaction $paymentTransaction) : string
    {
        if (static::isSetupTransaction($paymentTransaction)) {
            return static::getSetupRedirectUrl($paymentTransaction);
        }

        if (! $order = $paymentTransaction->getOrder()) {
            throw new MissingOrderException('No order present');
        }

        return add_query_arg([
            'wc-api'         => 'mwc_payments_stripe_complete_payment',
            'orderId'        => $order->getId() ?? '',
            'shouldTokenize' => $paymentTransaction->shouldTokenize(),
            '_wpnonce'       => wp_create_nonce('mwc_payments_stripe_complete_payment'),
        ], SiteRepository::getHomeUrl());
    }

    /**
     * Determines whether the transaction should use a setup intent instead of payment intent.
     *
     * @param PaymentTransaction $transaction
     *
     * @return bool
     */
    protected static function isSetupTransaction(PaymentTransaction $transaction) : bool
    {
        return ! $transaction->getTotalAmount() || ! $transaction->getTotalAmount()->getAmount() || static::isSubscriptionsChangingPaymentMethod();
    }

    /**
     * Determines if this form is being used to change a subscription's payment method.
     *
     * @return bool
     */
    protected static function isSubscriptionsChangingPaymentMethod() : bool
    {
        return class_exists('WC_Subscriptions') && ArrayHelper::get($_GET, 'change_payment_method');
    }

    /**
     * Gets the form redirect URL.
     *
     * @return string|null
     * @throws Exception
     */
    public function getRedirectUrl() : ?string
    {
        if (WooCommerceRepository::isCheckoutPayPage()) {
            return static::getPaymentRedirectUrl($this->getTransaction());
        } elseif (is_add_payment_method_page()) {
            return $this->getSetupRedirectUrl();
        }

        return null;
    }

    /**
     * Generates a redirect URL that will be used by the JS form handler for after payment method setup completes.
     *
     * @param PaymentTransaction|null $paymentTransaction
     *
     * @return string
     */
    public static function getSetupRedirectUrl(PaymentTransaction $paymentTransaction = null) : string
    {
        $args = [
            'wc-api'                => 'mwc_payments_stripe_complete_setup',
            '_wpnonce'              => wp_create_nonce('mwc_payments_stripe_complete_setup'),
            'change_payment_method' => static::isSubscriptionsChangingPaymentMethod(),
        ];

        if ($paymentTransaction && $order = $paymentTransaction->getOrder()) {
            $args['orderId'] = $order->getId();
        }

        return add_query_arg($args, SiteRepository::getHomeUrl());
    }

    /**
     * Gets the billing details for the current customer or order.
     *
     * This is passed to the Stripe JS and used to allow hiding their redundant fields.
     *
     * @return array
     */
    protected function getBillingDetails() : array
    {
        // ensure all properties are present for the JS
        $details = [
            'address' => [
                'city'        => '',
                'country'     => '',
                'line1'       => '',
                'line2'       => '',
                'postal_code' => '',
                'state'       => '',
            ],
            'email' => '',
            'name'  => '',
            'phone' => '',
        ];

        try {
            // if on the checkout pay page use the order's details
            if (WooCommerceRepository::isCheckoutPayPage() && $order = $this->getCurrentOrder()) {
                return ArrayHelper::combine($details, static::getOrderBillingDetails($order));
            }

            // return the current customer's address details if available
            if ($customer = $this->getCurrentCustomer()) {
                return ArrayHelper::combine($details, static::getCustomerBillingDetails($customer));
            }
        } catch (Exception $exception) {
        }

        // otherwise, return the default empty array
        return $details;
    }

    /**
     * Gets the Stripe billing details from the given order.
     *
     * @param Order $order
     *
     * @return array
     * @throws Exception
     */
    public static function getOrderBillingDetails(Order $order) : array
    {
        $details = [];

        ArrayHelper::set($details, 'email', $order->getEmailAddress() ?? '');

        if ($billingAddress = $order->getBillingAddress()) {
            $details = ArrayHelper::combine($details, static::getAddressDetails($billingAddress));
        }

        return $details;
    }

    /**
     * Gets the Stripe billing details from the given customer.
     *
     * @param Customer $customer
     *
     * @return array
     * @throws Exception
     */
    public static function getCustomerBillingDetails(Customer $customer) : array
    {
        $details = [];

        if ($wooCustomer = new WC_Customer($customer->getId())) {
            ArrayHelper::set($details, 'email', $wooCustomer->get_billing_email());
        }

        if ($billingAddress = $customer->getBillingAddress()) {
            $details = ArrayHelper::combine($details, static::getAddressDetails($billingAddress));
        }

        return $details;
    }

    /**
     * Gets the Stripe billing details from the given address.
     *
     * @param Address $address
     *
     * @return array
     */
    protected static function getAddressDetails(Address $address) : array
    {
        $addressLines = $address->getLines();

        return [
            'address' => [
                'city'        => $address->getLocality(),
                'country'     => $address->getCountryCode(),
                'line1'       => ArrayHelper::get($addressLines, 0, ''),
                'line2'       => ArrayHelper::get($addressLines, 1, ''),
                'postal_code' => $address->getPostalCode(),
                'state'       => ArrayHelper::get($address->getAdministrativeDistricts(), 0, ''),
            ],
            'name'  => trim($address->getFirstName().' '.$address->getLastName()),
            'phone' => $address->getPhone(),
        ];
    }

    /**
     * Gets the current customer associated with this payment form, if any.
     *
     * @return Customer|null
     */
    protected function getCurrentCustomer() : ?Customer
    {
        $wc = WooCommerceRepository::getInstance();

        return $wc && $wc->customer instanceof WC_Customer ? CustomerAdapter::getNewInstance($wc->customer)->convertFromSource() : null;
    }

    /**
     * Gets the current order associated with this payment form, if any.
     *
     * @return Order
     * @throws AdapterException|MissingOrderException
     */
    protected function getCurrentOrder() : Order
    {
        return OrderAdapter::getNewInstance($this->getOrderFromQueryVar())->convertFromSource();
    }
}
