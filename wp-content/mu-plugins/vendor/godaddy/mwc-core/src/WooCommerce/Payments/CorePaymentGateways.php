<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Traits\Features\IsConditionalFeatureTrait;
use GoDaddy\WordPress\MWC\Core\Payments\API;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\PaymentGatewayFirstActiveEvent;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Producers\OnboardingEventsProducer;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Producers\PaymentGatewayEventsProducer;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\Admin\PaymentMethodsListTable;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\ExternalCheckout;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\MyPaymentMethods;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\AbstractWalletGateway;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\ApplePayGateway;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\GooglePayGateway;

/**
 * Core payment gateways.
 *
 * Takes care of the necessary tasks for adding the core gateway(s) in a way that WooCommerce understands.
 */
class CorePaymentGateways
{
    use IsConditionalFeatureTrait;

    /** @var string[] classes to load as universal handlers */
    private $handlerClasses = [
        Captures::class,
        PaymentMethodsListTable::class,
        MyPaymentMethods::class,
        VirtualTerminal::class,
    ];

    /** @var class-string<AbstractPaymentGateway>[] payments gateways to load */
    protected static $paymentGatewayClasses = [
        GoDaddyPaymentsGateway::class,
        StripeGateway::class,
    ];

    /** @var AbstractPaymentGateway[] */
    private static $paymentGateways = [];

    /** @var class-string<AbstractWalletGateway>[] wallet gateways to load */
    protected static $walletGatewayClasses = [
        ApplePayGateway::class,
        GooglePayGateway::class,
    ];

    /** @var AbstractWalletGateway[] */
    private static $walletGateways = [];

    /**
     * Core payment gateways constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->addHooks();

        if (Onboarding::STATUS_CONNECTED === Onboarding::getStatus()) {
            static::$paymentGatewayClasses[] = GoDaddyPayInPersonGateway::class;
        }
    }

    /**
     * Loads the payments handlers.
     *
     * @internal callback
     * @see CorePaymentGateways::addHooks()
     *
     * @throws Exception
     */
    public function loadHandlers() : void
    {
        // don't load anything if we don't have any gateways enabled
        if (empty(static::getPaymentGateways())) {
            return;
        }

        foreach ($this->handlerClasses as $class) {
            new $class();
        }

        // TODO: load these as components once this class itself uses HasComponentsTrait {cwiseman 2021-10-21}
        (new OnboardingEventsProducer())->load();
        (new PaymentGatewayEventsProducer())->load();
        (new API())->load();

        if (ExternalCheckout::shouldLoad()) {
            (new ExternalCheckout())->load();
        }
    }

    /**
     * Adds instances of the gateways contained in this class to WooCommerce gateways.
     *
     * @internal callback
     * @see CorePaymentGateways::addHooks()
     * @see ApplePayGateway
     *
     * @param array|mixed $wcGateways
     * @return array|mixed
     * @throws Exception
     */
    public function loadPaymentGateways($wcGateways)
    {
        if (! ArrayHelper::accessible($wcGateways)) {
            return $wcGateways;
        }

        $gdGateways = ArrayHelper::wrap(static::getPaymentGateways());

        // Wallet gateways are loaded as ordinary WooCommerce gateways as they do not extend MWC abstract gateway model.
        // They should appear before the Pay-in-Person gateway.
        $gdGateways = ArrayHelper::insertBefore($gdGateways, ArrayHelper::wrap(static::getWalletGateways()), 'godaddy-payments-payinperson');

        // ensure Stripe appears last among GDP gateways
        if ($stripeGateway = ArrayHelper::get($gdGateways, 'stripe')) {
            ArrayHelper::remove($gdGateways, 'stripe');
            ArrayHelper::set($gdGateways, 'stripe', $stripeGateway);
        }

        // show GDP items on the top of the list
        return array_unique($gdGateways + $wcGateways, SORT_REGULAR);
    }

    /**
     * Registers the `woocommerce_payment_gateways` hook with loadPaymentGateways as the callback.
     *
     * @throws Exception
     */
    private function addHooks() : void
    {
        Register::action()
            ->setGroup('init')
            ->setHandler([$this, 'loadHandlers'])
            ->execute();

        Register::filter()
            ->setGroup('woocommerce_payment_gateways')
            ->setArgumentsCount(1)
            ->setHandler([$this, 'loadPaymentGateways'])
            ->execute();
    }

    /**
     * Broadcasts an event once the GDP gateway is active (available to be setup) for the first time.
     */
    protected static function maybeBroadcastPaymentGatewayFirstActiveEvent(AbstractPaymentGateway $gateway) : void
    {
        if (! Configuration::get('woocommerce.flags.broadcastGoDaddyPaymentsFirstActiveEvent')) {
            return;
        }

        Events::broadcast(new PaymentGatewayFirstActiveEvent($gateway->id));

        Configuration::set('woocommerce.flags.broadcastGoDaddyPaymentsFirstActiveEvent', false);

        update_option('gd_mwc_broadcast_go_daddy_payments_first_active', 'no');
    }

    /**
     * Gets a list of initialized core payment gateways.
     *
     * @return AbstractPaymentGateway[]
     * @throws Exception
     */
    public static function getPaymentGateways() : array
    {
        if (! empty(self::$paymentGateways)) {
            return self::$paymentGateways;
        }

        foreach (self::$paymentGatewayClasses as $class) {
            if (! is_a($class, AbstractPaymentGateway::class, true) || ! $class::isActive()) {
                continue;
            }

            $gateway = $class::getNewInstance();

            self::$paymentGateways[$gateway->id] = $gateway;

            self::maybeBroadcastPaymentGatewayFirstActiveEvent($gateway);
        }

        return self::$paymentGateways;
    }

    /**
     * Determines whether a gateway is a platform managed payment gateway, by ID.
     *
     * @param string $gatewayId
     * @return bool
     * @throws Exception
     */
    public static function isManagedPaymentGateway(string $gatewayId) : bool
    {
        return ArrayHelper::has(static::getPaymentGateways(), $gatewayId);
    }

    /**
     * Gets an instance of a platform managed payment gateway, for a given ID.
     *
     * @param string $gatewayId
     * @return AbstractPaymentGateway|null
     * @throws Exception
     */
    public static function getManagedPaymentGatewayInstance(string $gatewayId) : ?AbstractPaymentGateway
    {
        return ArrayHelper::get(static::getPaymentGateways(), $gatewayId);
    }

    /**
     * Gets a list of initialized wallet payment gateways.
     *
     * @return AbstractWalletGateway[]
     * @throws Exception
     */
    public static function getWalletGateways() : array
    {
        if (! empty(self::$walletGateways)) {
            return self::$walletGateways;
        }

        foreach (self::$walletGatewayClasses as $class) {
            if (! is_a($class, AbstractWalletGateway::class, true) || ! $class::isActive()) {
                continue;
            }

            $gateway = $class::getNewInstance();

            self::$walletGateways[$gateway->id] = $gateway;
        }

        return self::$walletGateways;
    }

    /**
     * Determines whether a gateway is a platform wallet payment gateway, by ID.
     *
     * @param string $gatewayId
     * @return bool
     * @throws Exception
     */
    public static function isWalletGateway(string $gatewayId) : bool
    {
        return ArrayHelper::has(static::getWalletGateways(), $gatewayId);
    }

    /**
     * Gets an instance of a platform wallet payment gateway, for a given ID.
     *
     * @param string $gatewayId
     * @return AbstractWalletGateway|null
     * @throws Exception
     */
    public static function getWalletGatewayInstance(string $gatewayId) : ?AbstractWalletGateway
    {
        return ArrayHelper::get(static::getWalletGateways(), $gatewayId);
    }

    /**
     * Determines that the feature can be loaded if WooCommerce is available.
     *
     * @return bool
     */
    public static function shouldLoadConditionalFeature() : bool
    {
        return WooCommerceRepository::isWooCommerceActive();
    }
}
