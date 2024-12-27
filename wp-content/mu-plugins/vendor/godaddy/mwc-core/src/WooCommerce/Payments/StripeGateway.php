<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\SanitizationHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Exceptions\Payments\PaymentsProviderSettingsException;
use GoDaddy\WordPress\MWC\Core\Features\Stripe\Stripe as StripeFeature;
use GoDaddy\WordPress\MWC\Core\Features\Worldpay\Worldpay;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\OrderPaymentTransactionDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Transactions\PaymentTransaction;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Adapters\TransactionPaymentIntentAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\DataSources\WooCommerce\AlternativePaymentToken;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\DataStores\WooCommerce\SessionPaymentIntentDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Gateways\PaymentIntentGateway;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Locales\LocalHelper;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Models\PaymentIntent;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Onboarding;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\Admin\PaymentMethodsListTable;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Integrations\SubscriptionsIntegration;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Stripe\Frontend\PaymentForm;
use GoDaddy\WordPress\MWC\Payments\Contracts\CardBrandContract;
use GoDaddy\WordPress\MWC\Payments\Events\PaymentTransactionEvent;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\AbstractPaymentMethod;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\CardPaymentMethod;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\AmericanExpressCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\DinersClubCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\DiscoverCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\MaestroCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\MastercardCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\VisaCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\Statuses\ApprovedTransactionStatus;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\Statuses\HeldTransactionStatus;
use Stripe\Stripe as StripeSDK;

/**
 * GoDaddy Stripe Native payment Gateway.
 */
class StripeGateway extends AbstractPaymentGateway
{
    /** Sends through sale and request for funds to be charged to cardholder's credit card. */
    const TRANSACTION_TYPE_CHARGE = 'charge';

    /** Sends through a request for funds to be "reserved" on the cardholder's credit card. */
    const TRANSACTION_TYPE_AUTHORIZATION = 'authorization';

    /** @var string provider name. */
    protected $providerName = 'stripe';

    /** @var PaymentForm */
    protected $paymentForm;

    /**
     * Constructs the gateway.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->id = $this->providerName;

        $this->title = __('Stripe', 'mwc-core');
        $this->method_description = sprintf(
            /* translators: Placeholders: %1$s & %3$s - <a> tag, %2$s & %4$s - </a> tag */
            __('Securely accept online payments via credit/debit card, wallets, bank redirects, and other methods enabled in your Stripe account. %1$sView Stripe pricing%2$s for your business\' location and payment methods. %3$sStripe terms apply%4$s.', 'mwc-core'),
            '<a href="https://stripe.com/pricing" target="_blank">',
            ' <span class="dashicons dashicons-external"></span></a>',
            '<a href="https://stripe.com/legal/" target="_blank">',
            ' <span class="dashicons dashicons-external"></span></a>'
        );

        $this->view_transaction_url = StringHelper::trailingSlash(Stripe::getDashboardUrl()).'payments/%s';

        // @NOTE: This property needs to be set to true in order to render payment form fields {ssmith1 2022-06-28}
        $this->has_fields = true;

        $this->updateConfigurationFromSettings([
            'enabled'         => 'enabled',
            'detailedDecline' => 'enable_detailed_decline_messages',
            'paymentMethods'  => 'enable_tokenization',
            'transactionType' => 'transaction_type',
            'debugMode'       => 'debug_mode',
        ]);

        // this needs to be registered before instantiating the payment form
        Register::action()
            ->setGroup('woocommerce_payment_token_class')
            ->setHandler([$this, 'getPaymentTokenClass'])
            ->setArgumentsCount(2)
            ->execute();

        $this->paymentForm = new PaymentForm($this->providerName);

        $this->init_form_fields();
        $this->init_settings();

        $this->enqueueScripts();
        $this->enqueueStyles();

        $this->setIntegrations();

        parent::__construct();

        Register::action()
                ->setGroup('woocommerce_update_options_payment_gateways_'.$this->id)
                ->setHandler([$this, 'process_admin_options'])
                ->execute();

        StripeSDK::setAppInfo(
            TypeHelper::string(Configuration::get('payments.stripe.api.appInfo.name'), ''),
            defined('MWC_CORE_VERSION') ? MWC_CORE_VERSION : null,
            TypeHelper::string(Configuration::get('payments.stripe.api.appInfo.url'), ''),
            TypeHelper::string(Configuration::get('payments.stripe.api.appInfo.partnerId'), ''),
        );
    }

    /**
     * Determines if the Stripe gateway is available to customers.
     *
     * @return bool
     */
    public function is_available() : bool
    {
        return $this->isParentAvailable() && Stripe::isConnected();
    }

    /**
     * Determines if the parent is_available() method is available.
     *
     * @return bool
     */
    protected function isParentAvailable() : bool
    {
        return parent::is_available();
    }

    /**
     * Gets the payment token class.
     *
     * @param mixed $class Payment token class.
     * @param string $type Token type.
     *
     * @return mixed
     */
    public function getPaymentTokenClass($class, string $type)
    {
        return ($type === 'mwc_stripe') ? AlternativePaymentToken::class : $class;
    }

    /**
     * Builds the gateway supports.
     *
     * @throws Exception
     */
    protected function buildSupports() : void
    {
        parent::buildSupports();

        // Stripe needs to set these explicitly
        if (Configuration::get("payments.{$this->providerName}.paymentMethods")) {
            $this->supports[] = 'tokenization';
            $this->supports[] = 'add_payment_method';
        }
    }

    /**
     * Get Menu Label.
     *
     * @return null|string
     */
    public function getMenuLabel() : ?string
    {
        return __('Stripe', 'mwc-core');
    }

    /**
     * Get Menu URL.
     *
     * @return null|string
     * @throws Exception
     */
    public function getMenuUrl() : ?string
    {
        return Stripe::getDashboardUrl();
    }

    /**
     * Determines if a menu item should be added.
     *
     * @return bool
     */
    public function shouldAddMenuItem() : bool
    {
        return static::canEnablePaymentGateway();
    }

    /**
     * Sets the gateway's integration handlers.
     *
     * @return self
     * @throws Exception
     */
    protected function setIntegrations()
    {
        // only load if saved payment methods are enabled
        if (Configuration::get("payments.{$this->providerName}.paymentMethods")) {
            $this->integrations['subscriptions'] = new SubscriptionsIntegration($this);
        }

        return $this;
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
               ->setHandle("{$this->id}-settings-js")
               ->setSource(WordPressRepository::getAssetsUrl('js/payments/stripe/admin/settings.js'))
               ->setDependencies([
                   'jquery',
               ])
               ->attachInlineScriptObject('MWCStripeSettings')
               ->attachInlineScriptVariables([
                   'confirmMessage' => __('You are about to disconnect your Stripe account. Your store will not be able to process transactions using Stripe until you connect an account again.', 'mwc-core'),
               ])
               ->execute();
    }

    /**
     * Determines if the scripts should be enqueued.
     *
     * @return bool
     */
    public function shouldEnqueueScripts() : bool
    {
        if (! Configuration::get('features.stripe.overrides.enabled', false)) {
            return false;
        }

        return $this->isStripeSettingsPage();
    }

    /**
     * Determines if the Stripe Settings page is displayed.
     *
     * @return bool
     */
    public function isStripeSettingsPage() : bool
    {
        return 'wc-settings' === ArrayHelper::get($_GET, 'page') &&
            'checkout' === ArrayHelper::get($_GET, 'tab') &&
            $this->providerName === ArrayHelper::get($_GET, 'section');
    }

    /**
     * Renders the admin options.
     */
    public function admin_options()
    {
        ?>
        <h2 class="mwc-payments-stripe-settings-title">
            <?php echo esc_html($this->get_title()); ?>
            <?php wc_back_link(__('Return to payments', 'mwc-core'), admin_url('admin.php?page=wc-settings&tab=checkout')); ?>
            <?php echo PaymentMethodsListTable::getStripeStatus(Onboarding::getStatus()); ?>
        </h2>

        <p class='mwc-payments-stripe-settings-description'>
            <?php echo wp_kses_post($this->method_description); ?>
        </p>

        <table class="form-table"><?php $this->generate_settings_html($this->get_form_fields()); ?></table>
        <?php
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
               ->setSource(WordPressRepository::getAssetsUrl('css/stripe-settings.css'))
               ->execute();
    }

    /**
     * Determines if the gateway should be active for use.
     *
     * @return bool
     * @throws BaseException|Exception
     */
    public static function isActive() : bool
    {
        if (! StripeFeature::shouldLoad()) {
            return false;
        }

        if (static::isStripePluginActive()) {
            return false;
        }

        return
            LocalHelper::isSupportedCountry($country = WooCommerceRepository::getBaseCountry())
            && LocalHelper::isSupportedCurrency($country, WooCommerceRepository::getCurrency())
            && ! Worldpay::shouldLoad();
    }

    /**
     * Process a Payment Transaction.
     *
     * @param PaymentTransaction $transaction
     *
     * @return PaymentTransaction
     *
     * @throws Exception
     */
    public function processPayment(PaymentTransaction $transaction) : PaymentTransaction
    {
        // if a $0 transaction, skip further processing
        if (! $transaction->getTotalAmount() || ! $transaction->getTotalAmount()->getAmount()) {
            return $this->processPaymentWithZeroTotal($transaction);
        }

        $gateway = PaymentIntentGateway::getNewInstance();
        $wc = WooCommerceRepository::getInstance();

        /* @phpstan-ignore-next-line */
        if ($wc && $wc->session) {
            $paymentIntent = ($paymentIntent = SessionPaymentIntentDataStore::getNewInstance()->read())
                ? $gateway->get($paymentIntent->getId())
                : PaymentIntent::getNewInstance();
        } else {
            $paymentIntent = PaymentIntent::getNewInstance();
        }

        $paymentIntent = TransactionPaymentIntentAdapter::getNewInstance($transaction)->convertFromSource($paymentIntent);
        $paymentIntent = $paymentIntent->getId() ? $gateway->update($paymentIntent) : $gateway->create($paymentIntent);

        $transaction->setRemoteId($paymentIntent->getId());

        // if paying with a saved method, confirm the payment directly
        if ($transaction->getPaymentMethod() && $transaction->getPaymentMethod()->getRemoteId()) {
            $transaction = $this->processPaymentWithPaymentMethod($transaction, $paymentIntent);
        }

        OrderPaymentTransactionDataStore::getNewInstance($this->providerName)->save($transaction);

        return $transaction;
    }

    /**
     * Processes a payment with a $0 total.
     *
     * @param PaymentTransaction $transaction
     *
     * @return PaymentTransaction
     * @throws Exception
     */
    protected function processPaymentWithZeroTotal(PaymentTransaction $transaction) : PaymentTransaction
    {
        if ($transaction->getPaymentMethod() && $transaction->getPaymentMethod()->getRemoteId()) {
            $transaction->setStatus(new ApprovedTransactionStatus());
        } else {
            $transaction->setStatus(new HeldTransactionStatus());
        }

        OrderPaymentTransactionDataStore::getNewInstance($this->providerName)->save($transaction);

        return $transaction;
    }

    /**
     * Processes a payment for a saved payment method.
     *
     * This confirms the payment directly with Stripe instead of via the JS form.
     *
     * @param PaymentTransaction $transaction
     * @param PaymentIntent $paymentIntent
     *
     * @return PaymentTransaction
     * @throws Exception
     */
    protected function processPaymentWithPaymentMethod(PaymentTransaction $transaction, PaymentIntent $paymentIntent) : PaymentTransaction
    {
        $paymentIntent = PaymentIntentGateway::getNewInstance()->confirm($paymentIntent, PaymentForm::getPaymentRedirectUrl($transaction));
        $wc = WooCommerceRepository::getInstance();

        // ensure subscribers are notified
        Events::broadcast(new PaymentTransactionEvent($transaction));

        /* @phpstan-ignore-next-line */
        if ($wc && $wc->session) {
            SessionPaymentIntentDataStore::getNewInstance()->delete($paymentIntent);
        }

        return TransactionPaymentIntentAdapter::getNewInstance($transaction)->convertToSource($paymentIntent);
    }

    /**
     * Gets a card payment method to add.
     *
     * @return AbstractPaymentMethod
     */
    protected function getPaymentMethodForAdd() : AbstractPaymentMethod
    {
        $nonce = SanitizationHelper::input($_POST['mwc-payments-{$this->providerName}-payment-nonce'] ?? '', false);

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
     * Checks if Stripe plugin is active.
     *
     * @return bool
     */
    protected static function isStripePluginActive() : bool
    {
        return function_exists('is_plugin_active') && is_plugin_active('woocommerce-gateway-stripe/woocommerce-gateway-stripe.php');
    }

    /**
     * Renders the payment fields.
     */
    public function payment_fields()
    {
        $this->paymentForm->render();
    }

    /**
     * Determines if Stripe Gateway can be enabled.
     *
     * @return bool
     */
    public static function canEnablePaymentGateway() : bool
    {
        return Onboarding::STATUS_CONNECTED === Onboarding::getStatus();
    }

    /**
     * Initialise settings form fields.
     *
     * @since 2.10.0
     */
    public function init_form_fields()
    {
        $this->form_fields = [
            'title' => [
                'title' => esc_html__('General', 'mwc-core'),
                'type'  => 'title',
            ],
            'enabled' => [
                'title'    => esc_html__('Enable', 'mwc-core'),
                'type'     => 'checkbox',
                'label'    => esc_html__('Enable to add the payment method to your checkout.', 'mwc-core'),
                'default'  => 'no',
                'disabled' => ! static::canEnablePaymentGateway(),
            ],
        ];

        switch (Onboarding::getStatus()) {
            case Onboarding::STATUS_CONNECTED:
                $this->form_fields['disconnect'] = [
                    'type'   => 'connect',
                    'css'    => 'border-color: #8995A9; color: #111111;',
                    'class'  => 'mwc-payments-stripe-connect button',
                    'action' => Onboarding::getDisconnectUrl(),
                    'text'   => __('Disconnect Stripe Account', 'mwc-core'),
                ];
                break;
            case Onboarding::STATUS_PENDING:
                $this->form_fields['pending'] = [
                    'type'        => 'connect',
                    'css'         => 'border-color: #8995A9; color: #111111;',
                    'class'       => 'mwc-payments-stripe-connect button disabled',
                    'action'      => '',
                    'text'        => __('Connecting to Stripe...', 'mwc-core'),
                    'disabled'    => true,
                    'description' => __('This operation can take up to a few seconds. Please reload this page to check the current status.', 'mwc-core'),
                ];
                break;
            case Onboarding::STATUS_DISCONNECTED:
            default:
                $this->form_fields['connect'] = [
                    'type'   => 'connect',
                    'css'    => 'border-color: #8995A9; color: #111111;',
                    'class'  => 'mwc-payments-stripe-connect button',
                    'action' => Onboarding::getStartUrl(),
                    'text'   => __('Connect Stripe Account', 'mwc-core'),
                ];
                break;
        }

        $this->form_fields['checkout_settings_title'] = [
            'title' => esc_html__('Checkout Settings', 'mwc-core'),
            'type'  => 'title',
            'class' => 'mwc-stripe-checkout_settings_title',
        ];
        $this->form_fields['accepted_card_brands'] = [
            'title'    => esc_html__('Accepted Card Logos', 'mwc-core'),
            'type'     => 'multiselect',
            'desc_tip' => esc_html__('These are the card logos that are displayed to customers as accepted during checkout.', 'mwc-core'),
            'default'  => array_keys($this->getAvailableCardBrands()),
            'class'    => 'wc-enhanced-select',
            'options'  => array_map(function ($brand) {
                return $brand->getLabel();
            }, $this->getAvailableCardBrands()),
        ];
        $this->form_fields['enable_tokenization'] = [
            'title'   => esc_html__('Saved Cards', 'mwc-core'),
            'type'    => 'checkbox',
            'label'   => esc_html__('Enable customers to securely save their payment cards to their account for future checkout.', 'mwc-core'),
            'default' => 'no',
        ];
        $this->form_fields['enable_detailed_decline_messages'] = [
            'title'   => esc_html__('Detailed Decline Messages', 'mwc-core'),
            'type'    => 'checkbox',
            'label'   => esc_html__('Enable detailed decline messages for customers during checkout rather than a generic decline message.', 'mwc-core'),
            'default' => 'yes',
        ];
        $this->form_fields['transaction_type'] = [
            'title'    => esc_html__('Transaction Type', 'mwc-core'),
            'type'     => 'select',
            'desc_tip' => esc_html__('Select how transactions should be processed. Charge submits all transactions for settlement, Authorization simply authorizes the order total for capture later.', 'mwc-core'),
            'default'  => self::TRANSACTION_TYPE_CHARGE,
            'options'  => [
                self::TRANSACTION_TYPE_CHARGE        => esc_html_x('Charge', 'noun, credit card transaction type', 'mwc-core'),
                self::TRANSACTION_TYPE_AUTHORIZATION => esc_html_x('Authorization', 'credit card transaction type', 'mwc-core'),
            ],
        ];
        $this->form_fields['debug_mode'] = [
            'title'    => esc_html__('Debug Mode', 'mwc-core'),
            'type'     => 'select',
            'desc_tip' => esc_html__('Show Detailed Error Messages and API requests/responses on the checkout page and/or save them to the debug log', 'mwc-core'),
            'default'  => self::DEBUG_MODE_OFF,
            'options'  => [
                self::DEBUG_MODE_OFF      => esc_html__('Off', 'mwc-core'),
                self::DEBUG_MODE_CHECKOUT => esc_html__('Show on Checkout Page', 'mwc-core'),
                self::DEBUG_MODE_LOG      => esc_html__('Save to Log', 'mwc-core'),
                self::DEBUG_MODE_BOTH     => esc_html__('Both', 'mwc-core'),
            ],
        ];
    }

    /**
     * Gets known payment methods with nice names for the connected account.
     *
     * @return string[]
     */
    public static function getKnownPaymentMethods() : array
    {
        return [
            'affirm'            => __('Affirm', 'mwc-core'),
            'afterpay_clearpay' => __('Afterpay / Clearpay', 'mwc-core'),
            'alipay'            => __('Alipay', 'mwc-core'),
            'au_becs_debit'     => __('BECS direct debit', 'mwc-core'),
            'bancontact'        => __('Bancontact', 'mwc-core'),
            'boleto'            => __('Boleto', 'mwc-core'),
            'card'              => __('Credit / Debit', 'mwc-core'),
            'eps'               => __('EPS', 'mwc-core'),
            'fpx'               => __('FPX', 'mwc-core'),
            'giropay'           => __('Giropay', 'mwc-core'),
            'grabpay'           => __('GrabPay', 'mwc-core'),
            'ideal'             => __('iDEAL', 'mwc-core'),
            'klarna'            => __('Klarna', 'mwc-core'),
            'konbini'           => __('Konbini', 'mwc-core'),
            'oxxo'              => __('OXXO', 'mwc-core'),
            'p24'               => __('P24', 'mwc-core'),
            'paynow'            => __('PayNow', 'mwc-core'),
            'sepa_debit'        => __('SEPA Debit', 'mwc-core'),
            'sofort'            => __('Sofort', 'mwc-core'),
            'us_bank_account'   => __('ACH Direct Debit', 'mwc-core'),
            'wechat_pay'        => __('WeChat Pay', 'mwc-core'),
        ];
    }

    /**
     * Gets available card types.
     *
     * @return CardBrandContract[]
     */
    protected function getAvailableCardBrands() : array
    {
        $brands = [];
        $brandObjects = [
            new AmericanExpressCardBrand(),
            new DinersClubCardBrand(),
            new DiscoverCardBrand(),
            new MaestroCardBrand(),
            new MastercardCardBrand(),
            new VisaCardBrand(),
        ];

        /** @var CardBrandContract $brandObject */
        foreach ($brandObjects as $brandObject) {
            $brands[$brandObject->getName()] = $brandObject;
        }

        return $brands;
    }

    /**
     * Generates Button HTML.
     *
     * @param string $key Field key.
     * @param array $data Field data.
     *
     * @return string
     */
    public function generate_connect_html(string $key, array $data) : string
    {
        $field_key = $this->get_field_key($key);

        $data = wp_parse_args($data, [
            'class'       => '',
            'css'         => '',
            'type'        => 'text',
            'action'      => '',
            'text'        => '',
            'disabled'    => false,
            'description' => '',
        ]);

        ob_start(); ?>
        <tr valign="top">
            <th scope="row" class="titledesc"></th>
            <td class="forminp">
                <a href="<?php echo $data['disabled'] ? '#' : esc_attr($data['action']); ?>"
                   class="<?php echo esc_attr($data['class']); ?> mwc-payments-connect"
                   id="<?php echo esc_attr($field_key); ?>" style="<?php echo esc_attr($data['css']); ?>" <?php echo $data['disabled'] ? 'aria-disabled="true"' : '' ?>>
                    <?php echo $data['text'] ?>
                </a>
                <?php if (ArrayHelper::has($data, 'description')) {
                    ?><p class="description"><?php echo esc_attr($data['description']); ?></p><?php
                } ?>
            </td>
        </tr>
        <?php

        return ob_get_clean();
    }

    /**
     * Gets the accepted card brands.
     *
     * @return CardBrandContract[]
     */
    protected function getAcceptedCardBrands() : array
    {
        $acceptedBrandNames = (array) $this->get_option('accepted_card_brands', []);
        $availableBrands = $this->getAvailableCardBrands();

        return array_intersect_key($availableBrands, array_flip($acceptedBrandNames));
    }

    /**
     * Gets the payment method icon, for display at checkout.
     *
     * @return false|string
     */
    public function get_icon()
    {
        $imageUrls = [];

        foreach ($this->getAcceptedCardBrands() as $brand) {
            try {
                $imageUrls[$brand->getName()] = WordPressRepository::getAssetsUrl("images/payments/cards/{$brand->getName()}.svg");
            } catch (Exception $exception) {
            }
        }

        if (empty($imageUrls)) {
            return '';
        }

        ob_start(); ?>
        <div class="mwc-payments-gateway-card-icons">

            <?php foreach ($imageUrls as $brandName => $imageUrl) : ?>
                <img src="<?php echo esc_url($imageUrl); ?>" alt="<?php echo esc_attr($brandName); ?>" width="40"
                     height="25" style="width: 40px; height: 25px;"/>
            <?php endforeach; ?>

        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Update configuration values based on WooCommerce settings.
     *
     * @param array<string, string>|null $configurations
     *
     * @return void
     *
     * @throws PaymentsProviderSettingsException
     */
    protected function updateConfigurationFromSettings($configurations = null) : void
    {
        $configurations = $configurations ?: array_keys($this->form_fields);

        parent::updateConfigurationFromSettings($configurations);
    }
}
