<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Exceptions\Payments\PaymentsProviderSettingsException;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\OrderTransactionDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Transactions\PaymentTransaction;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Exceptions\GatewayNotFoundException;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\CorePaymentGateways;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPaymentsGateway;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Traits\CanAutoEnablePaymentGatewayTrait;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\CardPaymentMethod;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\AbstractTransaction;
use WC_Order;
use WC_Payment_Gateway;

abstract class AbstractWalletGateway extends WC_Payment_Gateway
{
    use CanGetNewInstanceTrait;
    use CanAutoEnablePaymentGatewayTrait;

    /** @var string wallet ID */
    protected static string $walletId;

    /**
     * Abstract Wallet Gateway constructor.
     *
     * @throws Exception|PaymentsProviderSettingsException
     */
    public function __construct()
    {
        $this->id = 'godaddy-payments-'.static::getWalletId();

        $this->init_form_fields();
        $this->updateConfigurationFromSettings();
        $this->addHooks();
    }

    /**
     * Adds hooks.
     *
     * @throws Exception
     */
    protected function addHooks() : void
    {
        Register::action()
            ->setGroup("woocommerce_update_options_payment_gateways_{$this->id}")
            ->setHandler([$this, 'process_admin_options'])
            ->execute();

        Register::action()
            ->setGroup('admin_enqueue_scripts')
            ->setHandler([$this, 'enqueueAdminScriptsAndStyles'])
            ->execute();

        Register::action()
            ->setGroup('wp_enqueue_scripts')
            ->setHandler([$this, 'enqueueFrontendScriptsAndStyles'])
            ->execute();

        Register::filter()
            ->setGroup('woocommerce_order_get_payment_method_title')
            ->setHandler([$this, 'filterOrderPaymentMethodTitle'])
            ->setArgumentsCount(2)
            ->execute();
    }

    /**
     * Enqueues the gateway's admin scripts and styles.
     *
     * @internal callback
     * @see AbstractWalletGateway::addHooks()
     *
     * @throws Exception
     */
    public function enqueueAdminScriptsAndStyles() : void
    {
        $screen = WordPressRepository::getCurrentScreen();

        if (! $screen || "woocommerce_settings_checkout_{$this->id}" !== $screen->getPageId()) {
            return;
        }

        Enqueue::style()
            ->setHandle("{$this->id}-admin-settings")
            ->setSource(WordPressRepository::getAssetsUrl(sprintf('css/%s-settings.css', static::getWalletId())))
            ->execute();
    }

    /**
     * Updates configuration values based on WooCommerce settings.
     *
     * @see GoDaddyPaymentsGateway::updateConfigurationFromSettings()
     *
     * @throws PaymentsProviderSettingsException
     */
    public function updateConfigurationFromSettings() : void
    {
        $settingKeys = [
            'enabled'       => 'enabled',
            'enabled_pages' => 'enabledPages',
            'button_type'   => 'buttonType',
            'button_height' => 'buttonHeight',
            'button_style'  => 'buttonStyle',
        ];

        foreach ($settingKeys as $wcSettingKey => $configKey) {
            $settingValue = $this->get_option($wcSettingKey);

            if (is_numeric($settingValue)) {
                // ensure type consistency of numerical settings like the button height
                $settingValue = (int) $settingValue;
            } elseif ('yes' === $settingValue || 'no' === $settingValue) {
                // normalizes WooCommerce boolean settings into true boolean values
                $settingValue = 'yes' === $settingValue;
            }

            try {
                Configuration::set(static::getWalletConfigurationKey($configKey), $settingValue);
            } catch (Exception $exception) {
                throw new PaymentsProviderSettingsException(sprintf('%s configuration for %s could not be set.', $this->get_title(), $configKey), $exception);
            }
        }
    }

    /**
     * Enqueues the gateway's frontend scripts and styles.
     *
     * @internal callback
     * @see AbstractWalletGateway::addHooks()
     */
    public function enqueueFrontendScriptsAndStyles() : void
    {
        // TODO: implement in the Frontend epic {@itambek 2022-09-05}
    }

    /**
     * Filters the order payment method title for orders paid with the current wallet gateway.
     *
     * @internal filter callback
     *
     * @param string|mixed $title
     * @param WC_Order|mixed $order
     * @return string|mixed
     * @throws Exception
     */
    public function filterOrderPaymentMethodTitle($title, $order)
    {
        if ($order instanceof WC_Order && 'poynt' === $order->get_payment_method() && 'mwc_payments_'.StringHelper::snakeCase(static::getWalletId()) === $order->get_created_via()) {
            return $this->getOrderPaymentMethodTitle($order->get_id());
        }

        return $title;
    }

    /**
     * Returns the order payment method title for an order that was paid with the current wallet gateway.
     *
     * @param int $orderId
     * @return string
     * @throws Exception
     */
    public function getOrderPaymentMethodTitle(int $orderId) : string
    {
        $transaction = self::getPaymentTransactionForOrder($orderId);

        /** @var CardPaymentMethod|null $paymentMethod */
        $paymentMethod = $transaction->getPaymentMethod();

        return trim((string) preg_replace('/\s+/', ' ', sprintf(
            '%1$s %2$s (%3$s)',
            $paymentMethod && $paymentMethod->getBrand() ? $paymentMethod->getBrand()->getLabel() : __('Card', 'mwc-core'),
            $paymentMethod ? $paymentMethod->getLastFour() : '',
            // access the title directly to prevent it from being filtered (which may cause endless loops under certain scenarios)
            $this->title
        )));
    }

    /**
     * Processes a payment using the parent GoDaddy Payment gateway.
     *
     * Implements parent method.
     * @see GoDaddyPaymentsGateway::process_payment()
     *
     * @param int|mixed $orderId
     * @return string[]
     * @throws Exception|GatewayNotFoundException
     */
    public function process_payment($orderId) : array
    {
        /* @var GoDaddyPaymentsGateway|null $parentGateway */
        $parentGateway = CorePaymentGateways::getManagedPaymentGatewayInstance('poynt');

        if (! $parentGateway) {
            /* translators: Placeholder: %s - the gateway settings title, something like: "GoDaddy Payments - Apple Pay" */
            throw new GatewayNotFoundException(sprintf(__('Cannot load the %s gateway to process payment.', 'mwc-core'), $this->get_title()));
        }

        return $parentGateway->process_payment($orderId);
    }

    /**
     * Generates an HTML output for the parent gateway settings section.
     *
     * @see AbstractWalletGateway::init_form_fields()
     * @see AbstractWalletGateway::getParentGatewaySettingsSummaryHtml()
     * @see PaymentGatewayEventsProducer::didUserManuallyTriggeredPaymentGatewaySettingsChange()
     *
     * @param string $key unused, passed by WooCommerce
     * @param array<string, mixed> $data unused, passed by WooCommerce
     * @return string
     * @throws Exception
     */
    protected function generate_parent_gateway_settings_html(string $key = '', array $data = []) : string
    {
        /** @var GoDaddyPaymentsGateway $parentGateway */
        $parentGateway = CorePaymentGateways::getManagedPaymentGatewayInstance('poynt');

        if (! $parentGateway instanceof GoDaddyPaymentsGateway) {
            throw new GatewayNotFoundException(__('Cannot load the GoDaddy Payments gateway.', 'mwc-core'));
        }

        $gdpGatewaySettingsUrl = SiteRepository::getAdminUrl('admin.php?page=wc-settings&tab=checkout&section=poynt');
        $methodTitle = $parentGateway->get_method_title();
        $title = $parentGateway->get_title();
        $gdpGatewaySettingsTitle = $methodTitle === $title
            ? $title
            : $methodTitle.' - '.$title;
        $sectionDescription = sprintf(
            /* translators: Placeholder: %s - the gateway settings page title, normally: "GoDaddy Payments - Credit/Debit card" */
            esc_html__('These settings are managed from the %s page', 'mwc-core'),
            '<a href="'.esc_url($gdpGatewaySettingsUrl).'">'.$gdpGatewaySettingsTitle.'</a>'
        );

        ob_start(); ?>
        <div id="woocommerce-godaddy-payments-<?php echo esc_attr(static::getWalletId()); ?>-parent-gateway-settings-summary">
            <h3 class="wc-settings-sub-title" id="woocommerce_godaddy-payments-<?php echo esc_attr(static::getWalletId()); ?>-general-settings"><?php echo esc_html_x('General', 'General settings', 'mwc-core'); ?></h3>
            <p><?php echo $sectionDescription; ?></p>
            <div class="settings-table">
                <?php echo $this->getParentGatewaySettingsSummaryHtml($parentGateway); ?>
            </div>
        </div>

        <input type="hidden" name="woocommerce_<?php echo esc_attr($this->id); ?>_title"/>
        <?php
        // the hidden title input above ensures we can detect when user has saved the settings and broadcast events

        return ob_get_clean() ?: '';
    }

    /**
     * Generates an HTML output for the table rows summarizing the parent gateway settings.
     *
     * @see AbstractWalletGateway::admin_options()
     *
     * @param GoDaddyPaymentsGateway $parentGateway
     * @return string
     */
    protected function getParentGatewaySettingsSummaryHtml(GoDaddyPaymentsGateway $parentGateway) : string
    {
        ob_start();

        foreach ($parentGateway->get_form_fields() as $key => $fieldData) {
            if (! isset($fieldData['title']) || ! in_array($key, ['transaction_type', 'charge_virtual_orders', 'capture_paid_orders', 'enable_detailed_decline_messages', 'debug_mode'], true)) {
                continue;
            }

            $fieldLabel = $fieldData['title'];

            if ('transaction_type' === $key || 'debug_mode' === $key) {
                $valueLabel = $fieldData['options'][$parentGateway->get_option($key)];
            } else {
                $valueLabel = wc_string_to_bool($parentGateway->get_option($key)) ? __('Enabled', 'mwc-core') : __('Disabled', 'mwc-core');
            } ?>
            <div class="setting-row">
                <div class="setting-name"><?php echo ucfirst(strtolower(esc_html($fieldLabel))); ?>:</div>
                <div class="setting-value"><strong><?php echo esc_html($valueLabel); ?></strong></div>
            </div>
            <?php
        }

        return ob_get_clean() ?: '';
    }

    /**
     * Processes and saves the gateway settings.
     *
     * @throws PaymentsProviderSettingsException
     * @internal
     */
    public function process_admin_options() : bool
    {
        $updated = false;

        if (is_callable(parent::class.'::process_admin_options')) {
            $updated = (bool) parent::process_admin_options();
        }

        $this->updateConfigurationFromSettings();

        return $updated;
    }

    /**
     * Gets the payment transaction for a given order.
     *
     * @param int $orderId
     * @return AbstractTransaction|PaymentTransaction
     * @throws Exception
     */
    protected static function getPaymentTransactionForOrder(int $orderId) : AbstractTransaction
    {
        return OrderTransactionDataStore::getNewInstance('poynt')->read($orderId, 'payment');
    }

    /**
     * Determines whether the gateway is available.
     *
     * Implements parent method. We don't need this to be available as a normal WooCommerce payment gateway.
     * @see AbstractWalletGateway::isActive()
     *
     * @return false
     */
    public function is_available() : bool
    {
        return false;
    }

    /**
     * Determines whether the gateway still requires setup to function.
     *
     * When this gateway is toggled on via AJAX, if this returns true a
     * redirect will occur to the settings page instead.
     *
     * @return bool
     */
    public function needs_setup() : bool
    {
        return empty(Configuration::get(static::getWalletConfigurationKey('enabledPages')));
    }

    /**
     * Gets the wallet ID.
     *
     * @return string
     */
    public static function getWalletId() : string
    {
        return static::$walletId;
    }

    /**
     * Gets the full configuration key for the wallet, given the base key.
     *
     * @param string $key
     * @return string
     */
    public static function getWalletConfigurationKey(string $key) : string
    {
        return sprintf('payments.%s.%s', StringHelper::camelCase(static::getWalletId()), $key);
    }

    /**
     * Determines whether the gateway is enabled.
     *
     * @return bool
     */
    public static function isEnabled() : bool
    {
        return true === Configuration::get(static::getWalletConfigurationKey('enabled'));
    }

    /**
     * Gets the list of page contexts the gateway is enabled for.
     *
     * @return string[]
     */
    public static function getEnabledPages() : array
    {
        return ArrayHelper::wrap(Configuration::get(static::getWalletConfigurationKey('enabledPages')));
    }

    /**
     * Determines whether the gateway has at least one enabled page.
     *
     * @return bool
     */
    public static function hasEnabledPages() : bool
    {
        return ! empty(static::getEnabledPages());
    }

    /**
     * Checks whether the gateway is enabled for the given page context.
     *
     * @param string $context
     * @return bool
     */
    public static function isEnabledForPage(string $context) : bool
    {
        return ArrayHelper::contains(static::getEnabledPages(), $context);
    }

    /**
     * Determines whether the gateway is active.
     *
     * @return bool
     * @throws Exception
     */
    abstract public static function isActive() : bool;
}
