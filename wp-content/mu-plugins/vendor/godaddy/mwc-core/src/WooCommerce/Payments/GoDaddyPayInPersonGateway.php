<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Events\Exceptions\EventTransformFailedException;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\SanitizationHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Common\Sync\Jobs\PushSyncJob;
use GoDaddy\WordPress\MWC\Core\Exceptions\Payments\PaymentsProviderSettingsException;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\GoDaddyPayments;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Traits\HasGoDaddyPaymentsUrlsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Worldpay\Worldpay;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\OrderTransactionDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\ProductDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\ProductSyncEnabledEvent;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways\CatalogsGateway;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Models\Catalog;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Sync\Pull;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Sync\Push;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\OrderAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\Admin\PaymentMethodsListTable;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\Frontend\Admin\Notices;
use GoDaddy\WordPress\MWC\Dashboard\Exceptions\OrderNotFoundException;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\AbstractPaymentMethod;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\CardPaymentMethod;
use WC_Data_Store;
use WC_Order;
use WC_Shipping_Method;
use WC_Shipping_Zone;

/**
 * GoDaddy Pay In Person payment Gateway.
 */
class GoDaddyPayInPersonGateway extends AbstractPaymentGateway
{
    use HasGoDaddyPaymentsUrlsTrait;

    /** @var string method title. */
    public $method_title = 'GoDaddy Payments - Selling in Person';

    /** @var array Shipping methods that payment enabled for. If empty - accepted all the shipping methods */
    protected $enableForMethods;

    /** @var string provider name. */
    protected $providerName = 'godaddy-payments-payinperson';

    /** @var bool shipping methods validation status. */
    protected $isShippingMethodInvalid;

    /** @var string[] default shipping methods for GoDaddy Payments - Selling in Person */
    protected $defaultEnableForMethods = ['local_pickup', 'mwc_local_delivery', 'local_pickup_plus'];

    /** @var Catalog[] catalogs available for sync */
    protected $availableSyncCatalogs = [];

    /** @var string WooCommerce processing order status. */
    const PROCESSING_STATUS = 'processing';

    /**
     * Constructs the GoDaddy Payment Gateway.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->id = $this->providerName;
        $this->enableForMethods = ArrayHelper::wrap($this->get_option('enable_for_methods', $this->defaultEnableForMethods));

        $this->method_description = sprintf(
            /* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
            __('Let your customers buy online and pay in person with orders synced to your GoDaddy Smart Terminal. Get paid fast with deposits as soon as the next business day. %1$sGoDaddy Payments Terms apply%2$s.', 'mwc-core'),
            '<a href="'.GoDaddyPayments::getTermsOfServiceUrl().'" target="_blank">',
            ' <span class="dashicons dashicons-external"></span></a>'
        );

        if (Worldpay::shouldLoad()) {
            $this->method_title = __('Pay in Person', 'mwc-core');
            $this->method_description = __('Customers can buy online and pay in person with orders synced to your Terminals.', 'mwc-core');
        }

        $this->init_form_fields();
        $this->init_settings();
        $this->isShippingMethodInvalid = false;

        $this->updateConfigurationFromSettings([
            'enabled'           => 'enabled',
            'sync.push.enabled' => 'sync_push_enabled',
            'sync.pull.enabled' => 'sync_pull_enabled',
        ]);

        $this->maybeDisablePaymentGateway();

        $this->registerInformationHooks();

        parent::__construct();

        Register::action()
            ->setGroup('woocommerce_update_options_payment_gateways_'.$this->id)
            ->setHandler([$this, 'process_admin_options'])
            ->execute();

        Register::action()
            ->setGroup('admin_footer')
            ->setHandler([$this, 'renderProductDeleteModal'])
            ->execute();

        Register::action()
                ->setGroup('woocommerce_product_options_general_product_data')
                ->setHandler([$this, 'renderProductSyncBadge'])
                ->execute();

        $this->enqueueStyles();
        $this->enqueueScripts();
    }

    /**
     * Render the styles for the payment method.
     *
     * @throws Exception
     */
    protected function enqueueStyles()
    {
        Enqueue::style()
            ->setHandle("{$this->id}-main-styles")
            ->setSource(WordPressRepository::getAssetsUrl('css/pay-in-person-method.css'))
            ->execute();
    }

    /**
     * Determines if the scripts should be enqueued.
     *
     * @return bool
     */
    public function shouldEnqueueScripts() : bool
    {
        if (! Configuration::get('features.bopit_sync.enabled', false)) {
            return false;
        }

        return $this->isPayInPersonSettingsPage() ||
        $this->isProductEditPage();
    }

    /**
     * Determines if Pay In Person Settings page is displayed.
     *
     * @return bool
     */
    public function isPayInPersonSettingsPage() : bool
    {
        return 'wc-settings' === ArrayHelper::get($_GET, 'page') &&
            'checkout' === ArrayHelper::get($_GET, 'tab') &&
            $this->providerName === ArrayHelper::get($_GET, 'section');
    }

    /**
     * Determines if the product edit page is displayed.
     *
     * @return bool
     */
    public function isProductEditPage() : bool
    {
        return 'edit' === ArrayHelper::get($_GET, 'action') &&
        'product' === get_post_type(ArrayHelper::get($_GET, 'post'));
    }

    /**
     * Enqueues the scripts.
     *
     * @throws Exception
     */
    public function enqueueScripts()
    {
        if (! $this->shouldEnqueueScripts()) {
            return;
        }

        Enqueue::script()
               ->setHandle("{$this->id}-pay-in-person-js")
               ->setSource(WordPressRepository::getAssetsUrl('js/payments/godaddy-payments/admin/pay-in-person.js'))
               ->setDependencies([
                   'jquery',
                   'backbone',
                   'wc-backbone-modal',
               ])
               ->execute();
    }

    /**
     * Process the payment and return the result.
     *
     * This method is mainly used to change the default order status from 'On-Hold' to 'Processing'
     *
     * Note: this method is called by WooCommerce, so it needs to remain snake_case.
     *
     * @param $order_id
     * @param AbstractPaymentMethod $paymentMethod
     *
     * @return array
     */
    public function process_payment($order_id, AbstractPaymentMethod $paymentMethod = null) : array
    {
        try {
            $wooOrder = OrdersRepository::get($order_id);

            if (! $wooOrder) {
                throw new OrderNotFoundException('Order not found or WooCommerce is inactive');
            }

            $defaultOrderStatus = apply_filters('mwc_payments_'.$this->providerName.'_process_payment_order_status', static::PROCESSING_STATUS, $wooOrder);
            $wooOrder->update_status($defaultOrderStatus, __('Payment to be made upon delivery.', 'mwc-core'));

            // Remove cart.
            $woocommerce = WooCommerceRepository::getInstance();
            if (isset($woocommerce->cart)) {
                $woocommerce->cart->empty_cart();
            }

            return (array) apply_filters('mwc_payments_'.$this->providerName.'_after_process_payment', [
                'result'   => 'success',
                'redirect' => $this->get_return_url($wooOrder),
            ], $wooOrder);
        } catch (Exception $exception) {
            return [
                'result'  => 'failure',
                'message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Determines if the gateway should be active for use.
     *
     * @return bool
     * @throws Exception
     */
    public static function isActive() : bool
    {
        // bail early if BOPIT feature is purposefully disabled
        if (! Notices::isBOPITFeatureEnabled()) {
            return false;
        }

        // bail early if GoDaddy Payments status is declined or terminated or if Poynt was removed
        if (Onboarding::getStatus() === Onboarding::STATUS_DECLINED || 'no' === get_option('mwc_payments_poynt_active')) {
            return false;
        }

        // bail if not on the MWC non-reseller plan
        if (PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->isReseller()) {
            return false;
        }

        // consider it available if onboarding had been previously started
        if (Poynt::getServiceId()) {
            return true;
        }

        $woocommerce = WooCommerceRepository::getInstance();

        // otherwise adhere to the requirements for new users
        // TODO: we should look to move Woo methods like these under a WooCommerceConfiguration repository of sorts {@cwiseman 2021-05-28}
        return
            $woocommerce
            && $woocommerce->countries
            && 'US' === $woocommerce->countries->get_base_country()
            && 'USD' === get_woocommerce_currency();
    }

    /**
     * Renders the admin options.
     *
     * @throws Exception
     */
    public function admin_options()
    {
        ?>
        <h2 class="mwc-payments-godaddy-settings-title">
            <?php echo esc_html($this->get_method_title()); ?>
            <?php wc_back_link(__('Return to payments', 'mwc-core'), admin_url('admin.php?page=wc-settings&tab=checkout')); ?>
            <?php echo PaymentMethodsListTable::getGDPStatus(Onboarding::getStatus(), $this); ?>
        </h2>
        <?php
        if (Poynt::hasPoyntSmartTerminalActivated()) {
            ?>
            <p class="mwc-payments-godaddy-settings-description">
                <?php echo wp_kses_post($this->method_description); ?>
            </p>
            <p class="mwc-payments-godaddy-settings-description">
                <?php
                if (! Worldpay::shouldLoad()) {
                    printf(
                        esc_html__('%1$sShop Smart Terminal%2$s', 'mwc-core'),
                        '<a href="'.esc_url($this->getSmartTerminalProductPageUrl()).'" target="_blank">',
                        ' <span class="dashicons dashicons-external"></span></a>'
                    ); ?>
                    &nbsp;|&nbsp;
                    <?php
                } ?>

                <?php printf(
                    esc_html__('%1$sDevices%2$s', 'mwc-core'),
                    '<a href="'.esc_url($this->getDevicesUrl()).'" target="_blank">',
                    ' <span class="dashicons dashicons-external"></span></a>'
                ); ?>
                &nbsp;|&nbsp;
                <?php printf(
                    esc_html__('%1$sCatalogs%2$s', 'mwc-core'),
                    '<a href="'.esc_url($this->getCatalogUrl()).'" target="_blank">',
                    ' <span class="dashicons dashicons-external"></span></a>'
                ); ?>
                &nbsp;|&nbsp;
                <?php printf(
                    esc_html__('%1$sCustomize Terminal%2$s', 'mwc-core'),
                    '<a href="'.esc_url($this->getTerminalUrl()).'" target="_blank">',
                    ' <span class="dashicons dashicons-external"></span></a>'
                ); ?>
            </p>
            <?php
        }

        if (! Poynt::hasPoyntSmartTerminalActivated()) {
            $country = WooCommerceRepository::getBaseCountry();
            $salePrice = $country === 'CA' ? 'C$399' : '$399';
            $fullPrice = $country === 'CA' ? 'C$599' : '$499';

            $GLOBALS['hide_save_button'] = true; ?>
            <p>
                <?php echo __($this->method_description, 'mwc-core'); ?>
                <div class="mwc-payments-godaddy-settings-no-order">
                    <div class="mwc-payments-godaddy-settings-no-order-upper">
                        <h4><?php echo __('Smart Terminal', 'mwc-core'); ?></h4>
                        <h2><?php echo __('Dual screens for smoother selling.', 'mwc-core'); ?></h2>
                        <p><?php echo __('Our dual screens make checkout a breeze. Plus, our all-in-one terminal includes a built-in payment processor, scanner, printer, security and more.', 'mwc-core'); ?></p>
                    </div>
                    <div class="mwc-payments-godaddy-settings-no-order-lower">
                        <div class="mwc-payments-godaddy-settings-no-order-lower-inner">
                            <div class="mwc-payments-godaddy-settings-no-order-price">
                                <span class="mwc-payments-godaddy-settings-no-order-price-sale"><?php echo $salePrice; ?></span><span class="mwc-payments-godaddy-settings-no-order-price-linethrough"><?php echo $fullPrice; ?></span>
                            </div>
                            <div class="mwc-payments-godaddy-settings-no-order-badges">
                                <span class="mwc-payments-godaddy-settings-no-order-free"><?php echo __('Free', 'mwc-core'); ?></span>
                                <span class="mwc-payments-godaddy-settings-no-order-shipping"><?php echo __('2-day shipping.', 'mwc-core'); ?></span>
                            </div>
                            <div class="mwc-payments-godaddy-settings-no-order-btn">
                                <a target="_blank" href="<?php echo esc_url($this->getSmartTerminalProductPageUrl()); ?>"><?php echo __('Learn More', 'mwc-core'); ?></a>
                            </div>
                        </div>
                    </div>
                </div>
            </p>
            <?php
        } else {
            ?>
            <div class="mwc-payments-godaddy-sip" id="mwc-payments-godaddy-sip-settings">
                <div class="mwc-payments-godaddy-sip__title">
                    <?php echo __('Settings', 'mwc-core'); ?>
                </div>
                <table class="form-table">

                    <?php echo $this->generate_settings_html($this->get_form_fields(), false); ?>
                </table>
            </div>
            <!-- end of mwc-payments-godaddy-sip -->
            <?php
        }

        $this->display_errors();
    }

    /**
     * Determines if the gateway is available at checkout.
     *
     * Checks Woo's parent status, then that the site is connected and has credentials.
     *
     * Note: this method is called by WooCommerce, so it needs to remain snake_case.
     *
     * @return bool
     */
    public function is_available()
    {
        try {
            if (! $this->isParentAvailable() || ! $this->isChosenShippingMethodAccepted($this->enableForMethods) || ! $this->orderNeedsShipping()) {
                return false;
            }

            if (! $woocommerce = WooCommerceRepository::getInstance()) {
                return false;
            }

            if ($woocommerce->customer && 'US' !== $woocommerce->customer->get_shipping_country()) {
                return false;
            }
        } catch (Exception $exception) {
            // TODO: log the error {@cwiseman 2021-05-21}
            return false;
        }

        return true;
    }

    /**
     * Determines whether the cart / order needs shipping.
     *
     * @return bool
     * @throws Exception
     */
    public function orderNeedsShipping()
    {
        $woocommerce = WooCommerceRepository::getInstance();

        if ($woocommerce && $woocommerce->cart && $woocommerce->cart->needs_shipping()) {
            return apply_filters('woocommerce_cart_needs_shipping', true);
        }

        // @TODO: Replace this with internal functionality and Order Model {JO: 2021-10-08}
        if (0 < get_query_var('order-pay') && is_page(wc_get_page_id('checkout'))) {
            $orderId = absint(TypeHelper::scalar(get_query_var('order-pay'), 0));

            if ($order = OrdersRepository::get($orderId)) {
                return apply_filters('woocommerce_cart_needs_shipping', ! $this->orderIsVirtual($order));
            }
        }

        return apply_filters('woocommerce_cart_needs_shipping', false);
    }

    /**
     * Determines if the parent is_available() method is available.
     *
     * @return bool
     * @throws Exception
     */
    protected function isParentAvailable()
    {
        return parent::is_available();
    }

    /**
     * Registers the information hooks.
     *
     * @return void
     * @throws Exception
     */
    protected function registerInformationHooks()
    {
        Register::action()
            ->setGroup('woocommerce_email_before_order_table')
            ->setHandler([$this, 'instructionsEmail'])
            ->setPriority(20)
            ->setArgumentsCount(2)
            ->execute();
    }

    /**
     * Sets the order received text for the customer.
     *
     * @internal
     *
     * @param string|mixed $text
     * @param WC_Order|mixed $order
     * @return string|mixed
     */
    public function maybeSetOrderReceivedText($text, $order)
    {
        // Only show if the user checkout using this payment method
        if ($order instanceof WC_Order && $this->providerName === $order->get_payment_method()) {
            $text = $this->get_option('instructions', $this->getDefaultInstructions());
        }

        if (is_string($text)) {
            return wp_kses_post(wpautop(wptexturize($text)));
        }

        return $text;
    }

    /**
     * Adds instructions to WC order emails sent to the customers.
     *
     * @param WC_Order $order Order object.
     * @param bool $sentToAdmin Sent to admin.
     */
    public function instructionsEmail($order, $sentToAdmin)
    {
        if (! $sentToAdmin && $this->providerName === $order->get_payment_method()) {
            echo wp_kses_post(wpautop(wptexturize($this->get_option('instructions', $this->getDefaultInstructions()))).PHP_EOL);
        }
    }

    /**
     * Check is chosen on checkout shipping method accepted by current payment gateway.
     *
     * @param array $enabledForMethods
     *
     * @return bool
     * @throws Exception
     */
    private function isChosenShippingMethodAccepted(array $enabledForMethods = []) : bool
    {
        $woocommerce = WooCommerceRepository::getInstance();

        if (! empty($enabledForMethods && $woocommerce && $woocommerce->session)) {
            $chosenShippingMethod = current(ArrayHelper::wrap($woocommerce->session->get('chosen_shipping_methods')));

            if (! empty($chosenShippingMethod)) {
                $chosenShippingMethodNameElems = explode(':', $chosenShippingMethod);

                $available_methods = ArrayHelper::where($enabledForMethods, function ($method) use ($chosenShippingMethodNameElems) {
                    $methodElems = explode(':', $method);

                    return count($methodElems) == 1
                        // name consist of only 1 element - then it support any shipping method with current shipping name(id)
                        ? is_array($chosenShippingMethodNameElems) && ArrayHelper::contains(explode(':', $method), $chosenShippingMethodNameElems[0])
                        : implode(':', $chosenShippingMethodNameElems) === $method;
                });

                return ! empty($available_methods);
            }
        }

        return true;
    }

    /**
     * Can the order be refunded via this gateway?
     *
     * @param WC_Order $wcOrder Order object.
     * @return bool If false, the automatic refund button is hidden in the UI.
     * @throws BaseException
     */
    public function can_refund_order($wcOrder)
    {
        $order = $this->getOrderAdapter($wcOrder)->convertFromSource();

        $orderTransaction = $this->getOrderTransactionDataStore('poynt')->read($order->getId(), 'payment');

        if (! $orderTransaction->getRemoteId()) {
            return false;
        }

        return parent::can_refund_order($wcOrder);
    }

    /**
     * Gets the adapter for the given WooCommerce order.
     *
     * @param WC_Order $wcOrder
     * @return OrderAdapter
     */
    protected function getOrderAdapter($wcOrder) : OrderAdapter
    {
        return new OrderAdapter($wcOrder);
    }

    /**
     * Gets instance of data store for given provider's transaction.
     *
     * @param string $providerName
     * @return OrderTransactionDataStore
     */
    protected function getOrderTransactionDataStore(string $providerName) : OrderTransactionDataStore
    {
        return new OrderTransactionDataStore($providerName);
    }

    /**
     * Initializes the WooCommerce settings.
     */
    public function init_settings()
    {
        $this->initParentSettings();

        $this->has_fields = true;

        $this->title = $this->settings['title'] ?? $this->getDefaultTitle();
        $this->description = $this->settings['description'] ?? $this->getDefaultDescription();
    }

    /**
     * Initializes settings from the parent class.
     */
    protected function initParentSettings()
    {
        parent::init_settings();
    }

    /**
     * Renders the payment fields.
     */
    public function payment_fields()
    {
        ?>
        <p><?php echo wp_kses_post($this->get_description()); ?></p>
        <?php
    }

    /**
     * Gets the payment method icon, for display at checkout.
     *
     * @return string
     */
    public function get_icon()
    {
        try {
            $imageUrl = WordPressRepository::getAssetsUrl('images/payments/selling-in-person/checkout-icon.svg');
        } catch (Exception $exception) {
            return '';
        }

        if (empty($imageUrl)) {
            return '';
        }

        ob_start(); ?>
        <div class="mwc-payments-gateway-card-icons">
            <img src="<?php echo esc_url($imageUrl); ?>" alt="Pay in person icon" width="40" height="25" style="width: 40px; height: 25px;"/>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Gets the dismiss icon, for display in notices.
     *
     * @return string
     */
    public function getNoticeIconDismiss() : string
    {
        try {
            $imageUrl = WordPressRepository::getAssetsUrl('images/payments/selling-in-person/notice-dismiss.svg');
        } catch (Exception $exception) {
            return '';
        }

        if (empty($imageUrl)) {
            return '';
        }

        ob_start(); ?>
        <a href="#">
            <img src="<?php echo esc_url($imageUrl); ?>" alt="Dismiss Notice" width="12" height="12" />
        </a>
        <?php

        return ob_get_clean();
    }

    /**
     * Gets the warning icon, for display in notices.
     *
     * @return string
     */
    public function getNoticeIconWarning() : string
    {
        try {
            $imageUrl = WordPressRepository::getAssetsUrl('images/payments/selling-in-person/icon-warning-large.svg');
        } catch (Exception $exception) {
            return '';
        }

        if (empty($imageUrl)) {
            return '';
        }

        ob_start(); ?>
        <div class="wc-settings-notice__warning wc-settings-notice__icon">
            <img src="<?php echo esc_url($imageUrl); ?>" alt="Warning Notice" width="22" height="22" />
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Gets the info icon, for display in notices.
     *
     * @return string
     */
    public function getNoticeIconInfo() : string
    {
        try {
            $imageUrl = WordPressRepository::getAssetsUrl('images/payments/selling-in-person/notice-info.svg');
        } catch (Exception $exception) {
            return '';
        }

        if (empty($imageUrl)) {
            return '';
        }

        ob_start(); ?>
        <div class="wc-settings-notice__info wc-settings-notice__icon">
            <img src="<?php echo esc_url($imageUrl); ?>" alt="Information Notice" width="22" height="22" />
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Gets the GoDaddy icon, for display in modal.
     *
     * @return string
     */
    public function getGoIcon() : string
    {
        try {
            $imageUrl = WordPressRepository::getAssetsUrl('images/branding/gd-icon-gray.svg');
        } catch (Exception $exception) {
            return '';
        }

        if (empty($imageUrl)) {
            return '';
        }

        ob_start(); ?>
        <div class="mwc-payments-godaddy-product-delete__gd-icon">
            <img src="<?php echo esc_url($imageUrl); ?>" alt="GoDaddy Icon" width="28" height="28" />
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Gets the sync badge icon, for display in modal.
     *
     * @return string
     */
    public function getSyncBadgeIcon() : string
    {
        try {
            $imageUrl = WordPressRepository::getAssetsUrl('images/payments/selling-in-person/product-sync-badge.svg');
        } catch (Exception $exception) {
            return '';
        }

        if (empty($imageUrl)) {
            return '';
        }

        ob_start(); ?>
        <span class="mwc-payments-godaddy-product-sync__icon">
            <img src="<?php echo esc_url($imageUrl); ?>" alt="Sync Badge Icon" width="16" height="16" />
        </span>
        <?php

        return ob_get_clean();
    }

    /**
     * Gets a card payment method to add.
     *
     * @return AbstractPaymentMethod
     * @throws Exception
     */
    protected function getPaymentMethodForAdd() : AbstractPaymentMethod
    {
        $nonce = SanitizationHelper::input($_POST["mwc-payments-{$this->providerName}-payment-nonce"] ?? '', false);

        $paymentMethod = $this->getCardPaymentMethod()->setRemoteId($nonce);

        if ($currentUser = User::getCurrent()) {
            $paymentMethod->setCustomerId($currentUser->getId());
        }

        return $paymentMethod;
    }

    /**
     * Gets a new instance of card payment method.
     *
     * @return CardPaymentMethod
     */
    protected function getCardPaymentMethod() : CardPaymentMethod
    {
        return new CardPaymentMethod();
    }

    /**
     * Initialise settings form fields.
     */
    public function init_order_guide()
    {
    }

    /**
     * Initialise settings form fields.
     *
     * @throws Exception
     */
    public function init_form_fields()
    {
        $formFields = [
            'enabled' => [
                'title'    => esc_html__('Enable Selling in Person', 'mwc-core'),
                'type'     => 'checkbox',
                'label'    => '<span></span>',
                'default'  => 'no',
                'disabled' => ! static::canEnablePaymentGateway(),
            ],
            'title' => [
                'title'    => esc_html__('Checkout title', 'mwc-core'),
                'type'     => 'text',
                'desc_tip' => esc_html__('Payment method title that the customer will see during checkout.', 'mwc-core'),
                'default'  => $this->getDefaultTitle(),
            ],
            'description' => [
                'title'    => esc_html__('Checkout description', 'mwc-core'),
                'type'     => 'textarea',
                'desc_tip' => esc_html__('Payment method description that the customer will see during checkout.', 'mwc-core'),
                'default'  => $this->getDefaultDescription(),
                'css'      => 'max-width: 400px;',
            ],
            'instructions' => [
                'title'    => esc_html__('Order received instructions', 'mwc-core'),
                'type'     => 'textarea',
                'default'  => $this->getDefaultInstructions(),
                'desc_tip' => esc_html__('Message that the customer will see on the order received page and in the processing order email after checkout.', 'mwc-core'),
                'css'      => 'max-width: 400px;',
            ],
            'enable_for_methods' => [
                'title'             => __('Enable for Shipping Methods', 'mwc-core'),
                'type'              => 'multiselect',
                'class'             => 'wc-enhanced-select',
                'css'               => 'max-width: 400px;',
                'default'           => $this->defaultEnableForMethods,
                'options'           => $this->loadShippingMethodOptions(),
                'desc_tip'          => esc_html__('Select the shipping methods that will show this payment method for the customer during checkout.', 'mwc-core'),
                'custom_attributes' => [
                    'data-placeholder' => __('Select Shipping Methods', 'mwc-core'),
                ],
            ],
        ];

        $formFields = ArrayHelper::combine($formFields, $this->maybeAddSyncFields($formFields));
        $this->form_fields = $formFields;
    }

    /**
     * Validates MultiSelect Field to determine whether the user has selected any Shipping Method.
     *
     * Note: this method is called by WooCommerce, so it needs to remain snake_case.
     *
     * @return array|null
     */
    public function validate_multiselect_field($key, $value)
    {
        if ('enable_for_methods' === $key && empty($value)) {
            $this->isShippingMethodInvalid = true;
            $this->add_error(__('At least one shipping method is required to enable Selling in Person.', 'mwc-core'));
        }

        return $value;
    }

    /**
     * Gets the default gateway description.
     *
     * This is used to fill the Description setting for display at checkout.
     *
     * @return string
     */
    private function getDefaultDescription() : string
    {
        return esc_html__('Pay for your order in-person at pickup or delivery.', 'mwc-core');
    }

    /**
     * Gets the default instructions.
     *
     * This is used to get default instructions for the Thank you order page and email.
     *
     * @return string
     */
    private function getDefaultInstructions() : string
    {
        return esc_html__('We accept major credit/debit cards and cash.', 'mwc-core');
    }

    /**
     * Gets the default gateway title.
     *
     * This is used to fill the Title setting for display at checkout.
     *
     * @return string
     */
    private function getDefaultTitle() : string
    {
        return esc_html__('Pay in Person', 'mwc-core');
    }

    /**
     * Loads all the shipping method options for the enable_for_methods field.
     *
     * @param array $regions for which shipping methods will be loaded
     *
     * @return array
     * @throws Exception
     */
    protected function loadShippingMethodOptions(array $regions = ['US']) : array
    {
        $options = [];
        $woocommerce = WooCommerceRepository::getInstance();

        // Since this is expensive, we only want to do it if we're actually on the payment settings page.
        if (! $this->isAccessingSettings() || ! $woocommerce) {
            return [];
        }

        foreach ($woocommerce->shipping()->load_shipping_methods() as $method) {
            $options[$method->get_method_title()][$method->id] = $this->getShippingMethodOptionsText($method);

            foreach ($this->getAvailableShippingZones($regions) as $zone) {
                foreach ($zone->get_shipping_methods() as $shipping_method_instance_id => $shipping_method_instance) {
                    if ($shipping_method_instance->id !== $method->id) {
                        continue;
                    }

                    // Translators: %1$s shipping method title, %2$s shipping method id.
                    $option_instance_title = sprintf(esc_html__('%1$s (#%2$s)', 'mwc-core'), $shipping_method_instance->get_title(), $shipping_method_instance_id);

                    // Translators: %1$s zone name, %2$s shipping method instance name.
                    $option_title = sprintf(esc_html__('%1$s &ndash; %2$s', 'mwc-core'), $zone->get_id() ? $zone->get_zone_name() : esc_html__('Other locations', 'mwc-core'), $option_instance_title);

                    $options[$method->get_method_title()][$shipping_method_instance->get_rate_id()] = $option_title;
                }
            }
        }

        return $options;
    }

    /**
     * Return the methods shipping options text.
     *
     * @param array $availableRegions
     * @return array
     * @throws Exception
     */
    protected function getAvailableShippingZones(array $availableRegions = []) : array
    {
        if (! WooCommerceRepository::isWooCommerceActive()) {
            return [];
        }

        $zones = [];
        $rawZones = $this->getWooCommerceRawShippingZones();
        $rawZones[] = (object) ['zone_id' => 0];

        // add only zones with accepted regions
        foreach ($rawZones as $rawZone) {
            $zone = $this->getWooCommerceShippingZoneInstance($rawZone);

            $locations_filtered = ArrayHelper::where(ArrayHelper::wrap($zone->get_zone_locations()), function ($location) use ($availableRegions) {
                return empty($availableRegions) || ArrayHelper::contains($availableRegions, current(explode(':', $location->code)));
            });

            if (! empty($locations_filtered)) {
                $zones[] = $zone;
            }
        }

        return $zones;
    }

    /**
     * Gets WooCommerce raw shipping zones data list.
     *
     * @return array
     * @throws Exception
     */
    protected function getWooCommerceRawShippingZones() : array
    {
        $data_store = WC_Data_Store::load('shipping-zone');

        return $data_store ? (array) $data_store->get_zones() : [];
    }

    /**
     * Gets an instance of WooCommerce shipping zone object for the given raw zone data.
     *
     * @param mixed $rawZoneData
     * @return WC_Shipping_Zone
     */
    protected function getWooCommerceShippingZoneInstance($rawZoneData) : WC_Shipping_Zone
    {
        return new WC_Shipping_Zone($rawZoneData);
    }

    /**
     * Gets the method shipping options text.
     *
     * @param WC_Shipping_Method $method
     * @return string
     */
    protected function getShippingMethodOptionsText(WC_Shipping_Method $method) : string
    {
        return 'local_pickup_plus' === $method->id
            ? __('Local Pickup Plus method', 'mwc-core')
            /* translators: Placeholders: %1$s - Shipping Method name (for example FedEx, UPS or Local Pickup, etc.) */
            : sprintf(__('Any "%1$s" method', 'mwc-core'), $method->get_method_title());
    }

    /**
     * Checks to see whether the appropriate payment settings are being accessed by the current request.
     *
     * @return bool
     */
    protected function isAccessingSettings() : bool
    {
        if (WordPressRepository::isAdmin()) {
            // phpcs:disable WordPress.Security.NonceVerification
            if (! isset($_REQUEST['page']) || 'wc-settings' !== $_REQUEST['page']) {
                return false;
            }

            // @TODO: Add helper for these to WordPressRepository -- isCurrentTab {JO: 2021-09-16}
            if (! isset($_REQUEST['tab']) || 'checkout' !== $_REQUEST['tab']) {
                return false;
            }

            // @TODO: Add helper for these to WordPressRepository -- isCurrentSection {JO: 2021-09-16}
            if (! isset($_REQUEST['section']) || $this->providerName !== $_REQUEST['section']) {
                return false;
            }

            // phpcs:enable WordPress.Security.NonceVerification

            return true;
        }

        if ($this->isRestRequest() && StringHelper::contains($this->getCurrentQueryRestRoute(), '/payment_gateways')) {
            return true;
        }

        return false;
    }

    /**
     * Gets current WP Query REST API route.
     *
     * @return string
     */
    protected function getCurrentQueryRestRoute() : string
    {
        // @TODO: Add helper for this to WordPressRepository as we shouldn't be using globals directly {JO: 2021-09-16}
        global $wp;

        return $wp->query_vars['rest_route'] ?? '';
    }

    /**
     * Determines if the request is a REST API request.
     *
     * @return bool
     */
    protected function isRestRequest() : bool
    {
        return defined('REST_REQUEST') && REST_REQUEST;
    }

    /**
     * Update configuration values based on WooCommerce settings.
     *
     * @param null $configurations
     * @return void
     * @throws PaymentsProviderSettingsException
     */
    protected function updateConfigurationFromSettings($configurations = null)
    {
        $configurations = $configurations ?: array_keys($this->form_fields);

        parent::updateConfigurationFromSettings($configurations);

        // manually set these, as the parent handler doesn't allow arrays for some reason
        Configuration::set("payments.{$this->providerName}.sync.push.enabledCatalogIds", $this->get_option('sync_push_catalog_ids', []));
        Configuration::set("payments.{$this->providerName}.sync.pull.enabledCatalogIds", $this->get_option('sync_pull_catalog_ids', []));
    }

    /**
     * Determines whether the payment gateway can be managed.
     *
     * This is useful for things like disabling links & CTAs for the settings page.
     *
     * @return bool
     * @throws Exception
     */
    public static function canManage() : bool
    {
        // if this is a worldpay site, only allow management if a terminal is activated
        if (Worldpay::shouldLoad()) {
            return Poynt::hasPoyntSmartTerminalActivated();
        }

        // otherwise, determine by GDP status
        return Onboarding::canManagePaymentGateway(Onboarding::getStatus());
    }

    /**
     * Determines if Pay in Person can be enabled.
     *
     * @return bool
     * @throws Exception
     */
    public static function canEnablePaymentGateway() : bool
    {
        $isGdpSuspended = Onboarding::STATUS_SUSPENDED === Onboarding::getStatus();
        $isGdpTerminatedOrDisconnected = in_array(Onboarding::getStatus(), [Onboarding::STATUS_TERMINATED, Onboarding::STATUS_DISCONNECTED], true);
        $isPaymentsDisabled = ! Onboarding::paymentsEnabled();

        return Poynt::hasPoyntSmartTerminalActivated()
            && ! $isGdpTerminatedOrDisconnected
            && ! ($isGdpSuspended && $isPaymentsDisabled);
    }

    /**
     * May disable the payment gateway if it can't be enabled.
     *
     * @throws Exception
     */
    protected function maybeDisablePaymentGateway()
    {
        if (! static::canEnablePaymentGateway()) {
            $settings = get_option('woocommerce_godaddy-payments-payinperson_settings', []);

            if (isset($settings['enabled'])) {
                $settings['enabled'] = 'no';

                update_option('woocommerce_godaddy-payments-payinperson_settings', $settings);
            }
        }
    }

    /**
     * Gets the Smart Terminal product page URL.
     *
     * @return string
     * @throws Exception
     */
    protected function getSmartTerminalProductPageUrl() : string
    {
        return sprintf(
            Poynt::getHubUrl().'/payment-tools/96b9694e-1d63-47b0-85e6-3c773beaa004%s',
            ! empty($id = Poynt::getBusinessId()) ? '?businessId='.$id : ''
        );
    }

    /**
     * Gets the delete post link for a product.
     *
     * @param int|null $post
     * @return string
     */
    public function getDeletePostLink(int $post = null) : string
    {
        $link = null !== $post ? get_delete_post_link($post) : null;

        return is_string($link) ? $link : '';
    }

    /**
     * Renders the Product Delete modal.
     *
     * @throws Exception
     */
    public function renderProductDeleteModal()
    {
        if (! Configuration::get('features.bopit_sync.enabled', false) || ! $this->isProductEditPage()) {
            return;
        }

        $product = $this->getDataStore()->read((int) get_the_ID());

        if (! $product instanceof Product || ! $product->getRemoteId() || ! $this->getDeletePostLink()) {
            return;
        } ?>
        <script type="text/template" id="tmpl-mwc-payments-godaddy-product-delete">
            <div class="wc-backbone-modal mwc-payments-godaddy-product-delete">
                <div class="wc-backbone-modal-content">
                    <section class="wc-backbone-modal-main" role="main">
                        <header class="wc-backbone-modal-header">
                            <p><?php esc_html_e('Are you sure you want to move this product to trash?', 'mwc-core'); ?></p>
                        </header>
                        <article>
                            <p><?php esc_html_e('Are you sure you want to move this product to trash? Removing a synced product will also delete it from the In-Person catalog.', 'mwc-core'); ?></p>
                        </article>
                        <footer>
                            <?php echo $this->getGoIcon(); ?>
                            <div class="inner">
                                <a href="#" id="btn-cancel" class="modal-button modal-close"><?php esc_html_e('Cancel', 'mwc-core'); ?></a>
                                <a href="<?php echo esc_url($this->getDeletePostLink()); ?>" class="button-danger modal-button"><?php esc_html_e('Move to Trash', 'mwc-core'); ?></a>
                            </div>
                        </footer>
                    </section>
                </div>
            </div>
            <div class="wc-backbone-modal-backdrop modal-close"></div>
        </script>
        <?php
    }

    /**
     * Renders the product Sync badge conditionally.
     *
     * @throws Exception
     */
    public function renderProductSyncBadge()
    {
        if (! Configuration::get('features.bopit_sync.enabled', false)) {
            return;
        }

        $product = $this->getDataStore()->read((int) get_the_ID());

        if (! $product instanceof Product) {
            return;
        }

        if ($product->getRemoteId()) {
            switch ($product->getSource()) {
                case 'poynt':
                    $text = __('This product is synced from the In-Person Catalog', 'mwc-core');
                    break;

                default:
                    $text = __('This product is synced to the In-Person Catalog', 'mwc-core');
            } ?>
            <div class="option_group show_if_simple mwc-payments-godaddy-product-sync">
                <?php echo $this->getSyncBadgeIcon(); ?>
                <?php echo esc_html($text); ?>
            </div>
            <?php
        }
    }

    /**
     * Gets the product data store.
     *
     * @return ProductDataStore
     */
    protected function getDataStore() : ProductDataStore
    {
        return new ProductDataStore('poynt');
    }

    /**
     * Generates Switch HTML.
     *
     * @param string $key Field key.
     * @param array $data Field data.
     *
     * @return string
     */
    public function generate_switch_html(string $key, array $data) : string
    {
        $field_key = $this->get_field_key($key);

        $data = wp_parse_args($data, [
            'title'             => '',
            'label'             => '',
            'disabled'          => false,
            'class'             => '',
            'css'               => '',
            'type'              => 'text',
            'desc_tip'          => false,
            'description'       => '',
            'custom_attributes' => [],
        ]);

        ob_start(); ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?> <?php echo $this->get_tooltip_html($data); ?></label>
            </th>
            <td class="forminp">
                <fieldset class="mwc-payments-godaddy-sip-fieldset-switch">
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post($data['title']); ?></span></legend>
                    <label class="mwc-payments-godaddy-sip-switch" for="<?php echo esc_attr($field_key); ?>">
                        <input <?php disabled($data['disabled'], true); ?> class="<?php echo esc_attr($data['class']); ?> mwc-payments-godaddy-sip-switch__input" type="checkbox" name="<?php echo esc_attr($field_key); ?>" id="<?php echo esc_attr($field_key); ?>" style="<?php echo esc_attr($data['css']); ?>" value="1" <?php checked($this->get_option($key), 'yes'); ?> <?php echo $this->get_custom_attribute_html($data); ?> /><span class="mwc-payments-godaddy-sip-switch__slider"></span> <?php echo wp_kses_post($data['label']); ?></label>
                    <?php echo $this->get_description_html($data); ?>
                    <?php $this->maybeRenderStatusPills($key); ?>
                </fieldset>
            </td>
        </tr>
        <?php

        return ob_get_clean();
    }

    /**
     * Renders the connected/disconnected status pills if required.
     *
     * @param string $key
     */
    protected function maybeRenderStatusPills(string $key)
    {
        if ('sync_push_enabled' === $key) {
            $isEnabled = Push::isEnabled();
            $isHealthy = Push::isHealthy();
        } elseif ('sync_pull_enabled' === $key) {
            $isEnabled = Pull::isEnabled();
            $isHealthy = Pull::isHealthy();
        } else {
            return;
        }

        if (! $isEnabled) {
            return;
        }

        if ($isHealthy) {
            ?>
            <span class="mwc-payments-godaddy-sip-switch__connection-status mwc-payments-godaddy-sip-switch--connected">
                <?php esc_html_e('Connected', 'mwc-core'); ?>
            </span>
            <?php
        } else {
            ?>
            <span class="mwc-payments-godaddy-sip-switch__connection-status mwc-payments-godaddy-sip-switch--disconnected">
                <?php esc_html_e('Disconnected', 'mwc-core'); ?>
                <span class="tooltiptext">
                    <?php
                    /* translators: Placeholders: %1$s - <a>, %2$s - </a> tag */
                    printf(
                        esc_html__('One or more products in your catalog failed to sync. Please enable debug logging or reach out to %1$sour Care team%2$s for help.', 'mwc-core'),
                        '<a href="'.esc_url('/wp-admin/admin.php?page=godaddy-get-help').'">',
                        '</a>'
                    ); ?>
                </span>
            </span>
            <?php
        }
    }

    /**
     * Validates the switch field.
     *
     * This is a WooCommerce-required method and is processed by WC internally. As such, it is snake_case and we
     * don't typehint.
     *
     * @param $key
     * @param $value
     *
     * @return string
     */
    protected function validate_switch_field($key, $value)
    {
        return $this->validate_checkbox_field($key, $value);
    }

    /**
     * Generates Divider HTML.
     *
     * @return string
     */
    public function generate_divider_html() : string
    {
        ob_start(); ?>
        </table>
        <hr class="mwc-payments-godaddy-sip__divider">
        <table class="form-table">
        <?php

        return ob_get_clean();
    }

    /**
     * Generates the push sync notices.
     *
     * @return string
     */
    public function generate_sync_push_notices_html() : string
    {
        $html = '';

        // no notices if sync is disabled
        if ('yes' !== $this->get_option('sync_push_enabled')) {
            return $html;
        }

        foreach (array_diff(Push::getSyncedCatalogIds(), Push::getEnabledCatalogIds()) as $removedCatalogId) {
            $html .= $this->generate_notice_html(
                'sync_push_catalogs_removed_notice',
                [
                    'title' => sprintf(
                        esc_html__('%1$s has been removed. Products will no longer be synced to this catalog. To remove any products already synced you must delete them manually.', 'mwc-core'),
                        $this->getCatalogName($removedCatalogId)
                    ),
                    'class' => 'pay-in-person-notice--warning',
                    'icon'  => 'warning',
                ]
            );
        }

        return $html;
    }

    /**
     * Generates the pull sync notices.
     *
     * @return string
     */
    public function generate_sync_pull_notices_html() : string
    {
        $html = '';

        if ('yes' !== $this->get_option('sync_pull_enabled')) {
            if (count(Pull::getSyncedCatalogIds())) {
                $html .= $this->generate_notice_html(
                    'sync_pull_disabled',
                    [
                        'title' => __('In-Person product sync to WooCommerce has been disabled. Products will no longer sync to WooCommerce. To remove any products already synced you must delete them manually.', 'mwc-core'),
                        'class' => 'pay-in-person-notice--warning',
                        'icon'  => 'warning',
                    ]);
            }

            return $html;
        }

        foreach (array_diff(Pull::getEnabledCatalogIds(), array_keys($this->getSyncCatalogOptions())) as $removedCatalogId) {
            $html .= $this->generate_notice_html(
                'sync_pull_catalogs_removed_notice',
                [
                    'title' => sprintf(
                        esc_html__('%1$s has been removed from Selling In-Person. This catalog will no longer sync to WooCommerce', 'mwc-core'),
                        $this->getCatalogName($removedCatalogId)
                    ),
                    'class' => 'pay-in-person-notice--info',
                    'icon'  => 'info',
                ]
            );
        }

        return $html;
    }

    /**
     * Generates Notice HTML.
     *
     * @param string $key
     * @param array $data
     *
     * @return string
     */
    public function generate_notice_html(string $key, array $data) : string
    {
        $field_key = $this->get_field_key($key);

        $data = wp_parse_args($data, [
            'class' => '',
            'title' => '',
            'icon'  => '',
        ]);

        $iconMethod = ! empty($data['icon']) ? "getNoticeIcon{$data['icon']}" : '';

        ob_start(); ?>
        </table>
        <div class="wc-settings-notice <?php echo esc_attr($data['class']); ?>" id="<?php echo esc_attr($field_key); ?>">
            <?php echo method_exists($this, $iconMethod) ? $this->{$iconMethod}() : ''; ?>
            <div class="wc-settings-notice__content"><?php echo esc_html($data['title']); ?></div>
            <div class="wc-settings-notice__dismiss"><?php echo $this->getNoticeIconDismiss() ?></div>
        </div>
        <table class="form-table">
        <?php

        return ob_get_clean();
    }

    /**
     * Determines whether BOPIT sync fields should be added.
     *
     * @param array $formFields
     *
     * @return array
     */
    public function maybeAddSyncFields(array $formFields) : array
    {
        if (! Configuration::get('features.bopit_sync.enabled', false)) {
            return $formFields;
        }

        $formFields['divider'] = [
            'title'   => '',
            'type'    => 'divider',
            'label'   => '<span></span>',
            'default' => 'no',
        ];

        $formFields['bopit-sync-title'] = [
            /* translators: We are prompting the user to sync their products with in-person product catalog(s) - refers to products sold in-person, e.g. via POS terminal */
            'title'       => __('In-Person Catalog Sync', 'mwc-core'),
            'type'        => 'title',
            'description' => sprintf(
                /* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
                __('Sync your WooCommerce products with In-Person catalog(s). %1$sLearn more%2$s', 'mwc-core'),
                '<a href="https://www.godaddy.com/help/41073" target="_blank">', '</a>'
            ),
        ];

        $catalogOptions = $this->getSyncCatalogOptions();

        $formFields['sync_push_enabled'] = [
            /* translators: We are prompting the user to sync their products with in-person product catalog(s) - refers to products sold in-person, e.g. via POS terminal */
            'title'   => esc_html__('Sync WooCommerce products to In-Person', 'mwc-core'),
            'type'    => 'switch',
            'class'   => '',
            'label'   => '<span></span>',
            'default' => 'no',
            /* translators: We are prompting the user to sync their products with in-person product catalog(s) - refers to products sold in-person, e.g. via POS terminal */
            'desc_tip' => esc_html__('Basic WooCommerce product data is pushed to the selected In-Person catalog(s) for simple products.', 'mwc-core'),
        ];

        $formFields['sync_push_catalog_ids'] = [
            'title'   => '',
            'type'    => 'multiselect',
            'class'   => 'pay-in-person-sync-select wc-enhanced-select',
            'css'     => 'max-width: 400px; margin-top: -24px;',
            'default' => ArrayHelper::wrap(current(array_keys($catalogOptions))),
            'options' => $catalogOptions,
            /* translators: In-person catalog(s) refers to products sold in-person, e.g. via POS terminal */
            'description' => '<span class="pay-in-person-sync-select--error">'.__('To sync to In-Person you must have at least one catalog selected.', 'mwc-core').'</span>',
        ];

        $formFields['sync_push_notices'] = [
            'type' => 'sync_push_notices',
        ];

        $formFields['sync_pull_enabled'] = [
            'title'   => esc_html__('Sync In-Person products to WooCommerce', 'mwc-core'),
            'type'    => 'switch',
            'class'   => '',
            'label'   => '<span></span>',
            'default' => 'no',
            /* translators: In-person catalog(s) refers to products sold in-person, e.g. via POS terminal */
            'desc_tip' => esc_html__('Product data is fetched from the selected In-Person catalog(s). Sync excludes products with custom prices or "any combination" modifiers.', 'mwc-core'),
        ];

        $formFields['sync_pull_catalog_ids'] = [
            'title'       => '',
            'type'        => 'multiselect',
            'class'       => 'pay-in-person-sync-select wc-enhanced-select',
            'css'         => 'max-width: 400px; margin-top: -24px;',
            'default'     => ArrayHelper::wrap(current(array_keys($catalogOptions))),
            'options'     => $catalogOptions,
            'description' => '<span class="pay-in-person-sync-select--error">'.__('To sync to WooCommerce you must have at least one catalog selected.', 'mwc-core').'</span>',
        ];

        $formFields['sync_pull_notices'] = [
            'type' => 'sync_pull_notices',
        ];

        return $formFields;
    }

    /**
     * Processes admin options.
     *
     * This is overridden to broadcast sync-enabled events and initiate full syncs when the settings are newly enabled.
     *
     * Note: using get_option is necessary because our configurations won't have been updated yet.
     *
     * @return bool
     * @throws PaymentsProviderSettingsException
     * @throws EventTransformFailedException
     */
    public function process_admin_options()
    {
        // save the status before processing the admin options
        $pushWasEnabled = 'yes' === $this->get_option('sync_push_enabled');
        $pullWasEnabled = 'yes' === $this->get_option('sync_pull_enabled');

        // save previously enabled catalog ids before processing the admin options
        $previouslyEnabledPushCatalogIds = (array) $this->get_option('sync_push_catalog_ids');
        $previouslyEnabledPullCatalogIds = (array) $this->get_option('sync_pull_catalog_ids');

        $result = $this->processParentAdminOptions();

        if (! Configuration::get('features.bopit_sync.enabled', false)) {
            return $result;
        }

        // initiate syncs if the setting was previously disabled and is now enabled
        if ($result) {
            $this->maybeTriggerPushSync($pushWasEnabled, $previouslyEnabledPushCatalogIds);
            $this->maybeTriggerPullSync($pullWasEnabled, $previouslyEnabledPullCatalogIds);
        }

        return $result;
    }

    /**
     * Processes the parent gateway class admin options.
     *
     * @return bool
     * @throws PaymentsProviderSettingsException
     */
    protected function processParentAdminOptions()
    {
        return parent::process_admin_options();
    }

    /**
     * Triggers a push sync if previously disabled, or new catalogs have been added.
     *
     * @TODO: the sync could be optimized by skipping updating products when only new catalogs were added {@itambek 2022-03-07}
     *
     * @param bool $wasEnabled
     * @param array $oldCatalogIds
     * @throws EventTransformFailedException
     * @throws Exception
     */
    protected function maybeTriggerPushSync(bool $wasEnabled, array $oldCatalogIds) : void
    {
        if (! $this->shouldTriggerSync('push', $wasEnabled, $oldCatalogIds)) {
            return;
        }

        Events::broadcast(new ProductSyncEnabledEvent('push'));

        Push::start();
    }

    /**
     * Triggers a pull sync if previously disabled.
     *
     * @param bool $wasEnabled
     * @param array $oldCatalogIds
     * @throws EventTransformFailedException
     * @throws Exception
     */
    protected function maybeTriggerPullSync(bool $wasEnabled, array $oldCatalogIds) : void
    {
        if (! $this->shouldTriggerSync('pull', $wasEnabled, $oldCatalogIds)) {
            return;
        }

        Events::broadcast(new ProductSyncEnabledEvent('pull'));

        $this->maybeRegisterWebhooks();

        Pull::start();
    }

    /**
     * Determines whether it should trigger sync in the given direction.
     *
     * @param string $direction
     * @param bool $wasEnabled
     * @param array $oldCatalogIds
     * @return bool
     */
    protected function shouldTriggerSync(string $direction, bool $wasEnabled, array $oldCatalogIds) : bool
    {
        $isEnabled = wc_string_to_bool($this->get_option("sync_{$direction}_enabled")); // wc_string_to_bool handles all falsy/truthy values for us
        $syncToggled = $isEnabled && ! $wasEnabled;
        $newCatalogsAdded = $isEnabled && ! empty(array_diff((array) $this->get_option("sync_{$direction}_catalog_ids"), $oldCatalogIds));

        return $syncToggled || $newCatalogsAdded;
    }

    /**
     * Kicks off a job to register product webhooks if they aren't already registered.
     *
     * @throws Exception
     */
    protected function maybeRegisterWebhooks()
    {
        if (get_option('mwc_payments_poynt_product_webhooksRegistered')) {
            return;
        }

        PushSyncJob::create([
            'owner'      => 'register_poynt_webhooks',
            'batchSize'  => 2,
            'objectType' => 'webhooks',
            'objectIds'  => [
                'CATALOG_UPDATED',
                'PRODUCT_UPDATED',
            ],
        ]);
    }

    /**
     * Gets the catalog options for use in multi-selects.
     *
     * @return array
     */
    protected function getSyncCatalogOptions() : array
    {
        $options = [];

        foreach ($this->getAvailableSyncCatalogs() as $catalog) {
            $options[$catalog->getRemoteId()] = $catalog->getName();
        }

        $this->setSyncCatalogNames($options);

        return $this->setDefaultSyncCatalog($options);
    }

    /**
     * Sets the default catalog name for use in multi-selects.
     *
     * @param array $options
     * @return array
     */
    protected function setDefaultSyncCatalog(array $options) : array
    {
        if (empty($options)) {
            return $options;
        }

        $defaultText = '(Default)';
        $hasDefaultName = false;

        foreach ($options as $id => $name) {
            if ($name === get_bloginfo()) {
                $options[$id] = "{$name} {$defaultText}";
                $hasDefaultName = true;
                $options = array_merge([$id => $options[$id]], $options);
                break;
            }
        }

        if (! $hasDefaultName) {
            $options[array_keys($options)[0]] .= " {$defaultText}";
        }

        return $options;
    }

    /**
     * Sets the catalog options for use in multi-selects.
     *
     * @param array<mixed> $options
     * @return bool
     */
    protected function setSyncCatalogNames(array $options) : bool
    {
        return update_option('mwp_payments_sync_catalog_options', array_merge($options, TypeHelper::array(get_option('mwp_payments_sync_catalog_options', []), [])));
    }

    /**
     * Gets the catalogs available for sync.
     *
     * This can be used for populating the options and other display. It should be used sparingly and only on specific
     * pages, as getting the catalogs list is expensive.
     *
     * @return Catalog[]
     */
    protected function getAvailableSyncCatalogs() : array
    {
        // only get catalogs on the settings page
        if (empty($this->availableSyncCatalogs) && $this->isAccessingSettings()) {
            try {
                $this->availableSyncCatalogs = CatalogsGateway::getNewInstance()->getList();
            } catch (Exception $e) {
                SentryException::getNewInstance($e->getMessage(), $e);

                $this->availableSyncCatalogs = [];
            }
        }

        return $this->availableSyncCatalogs;
    }

    /**
     * Gets a catalog's name.
     *
     * @param string $catalogId
     *
     * @return string
     */
    protected function getCatalogName(string $catalogId) : string
    {
        $storedOptions = get_option('mwp_payments_sync_catalog_options', []);

        if ($name = ArrayHelper::get($storedOptions, $catalogId)) {
            return $name;
        }

        return $this->getSyncCatalogOptions()[$catalogId] ?? $catalogId;
    }
}
