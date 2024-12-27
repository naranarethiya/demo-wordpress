<?php

namespace GoDaddy\WordPress\MWC\Core;

use Exception;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notices;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentLoadFailedException;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsFromContainerTrait;
use GoDaddy\WordPress\MWC\Common\Plugin\BasePlatformPlugin;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Common\Traits\IsSingletonTrait;
use GoDaddy\WordPress\MWC\Core\API\API;
use GoDaddy\WordPress\MWC\Core\Auth\API\API as AuthenticationAPI;
use GoDaddy\WordPress\MWC\Core\Auth\Sso\WordPress\WordPressSso;
use GoDaddy\WordPress\MWC\Core\Client\Client;
use GoDaddy\WordPress\MWC\Core\Events\Producers;
use GoDaddy\WordPress\MWC\Core\FeatureFlags\ConfigurationLoader;
use GoDaddy\WordPress\MWC\Core\Features\Assistant\Assistant;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\CartRecoveryEmails;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Backfill\CommerceBackfill;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\CommerceCustomerPush;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Polling\CommercePolling;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\CommerceWebhooks;
use GoDaddy\WordPress\MWC\Core\Features\CostOfGoods\CostOfGoods;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\EmailNotifications;
use GoDaddy\WordPress\MWC\Core\Features\ExternalDomainControls\ExternalDomainControls;
use GoDaddy\WordPress\MWC\Core\Features\GiftCertificates\GiftCertificates;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\GoDaddyPayments;
use GoDaddy\WordPress\MWC\Core\Features\GoogleAnalytics\GoogleAnalytics;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Marketplaces;
use GoDaddy\WordPress\MWC\Core\Features\Onboarding\Dashboard as OnboardingDashboard;
use GoDaddy\WordPress\MWC\Core\Features\Onboarding\Onboarding;
use GoDaddy\WordPress\MWC\Core\Features\PluginControls\PluginControls;
use GoDaddy\WordPress\MWC\Core\Features\SequentialOrderNumbers\SequentialOrderNumbers;
use GoDaddy\WordPress\MWC\Core\Features\Shipping\Shipping;
use GoDaddy\WordPress\MWC\Core\Features\UrlCoupons\UrlCoupons;
use GoDaddy\WordPress\MWC\Core\Features\Worldpay\Worldpay;
use GoDaddy\WordPress\MWC\Core\Interceptors\Interceptors;
use GoDaddy\WordPress\MWC\Core\Pages\Plugins\IncludedWooCommerceExtensionsTab;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\OrderSynchronization;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\ViewOrderPage;
use GoDaddy\WordPress\MWC\Core\Webhooks\Webhooks;
use GoDaddy\WordPress\MWC\Core\WooCommerce\ExtensionsTab;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Overrides\Overrides;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\CorePaymentGateways;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\CoreShippingMethods;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\LocalPickup;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\RemoveShipmentTrackingFromManagedWordPressSites;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\ShipmentTracking;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Updates;

/**
 * MWC Core package class.
 */
class Package extends BasePlatformPlugin
{
    use HasComponentsFromContainerTrait;
    use IsSingletonTrait;

    /** @var string plugin name */
    protected $name = 'mwc-core';

    /** @var array classes to instantiate */
    protected $classesToInstantiate = [
        CorePaymentGateways::class                             => 'web',
        ExtensionsTab::class                                   => 'web',
        Producers::class                                       => 'web',
        RemoveShipmentTrackingFromManagedWordPressSites::class => 'web',
        ShipmentTracking::class                                => 'web',
        LocalPickup::class                                     => 'web',
        CoreShippingMethods::class                             => 'web',
        Updates::class                                         => 'web',
        Client::class                                          => 'web',
        IncludedWooCommerceExtensionsTab::class                => 'web',
        ViewOrderPage::class                                   => 'web',

        // TODO: is this overkill? is there a better place to be loading this? {JS - 2021-10-17}
        OrderSynchronization::class => true,
    ];

    /** @var class-string<ComponentContract>[] */
    protected $componentClasses = [
        Assistant::class,
        GiftCertificates::class,
        Onboarding::class,
        OnboardingDashboard::class,
        Overrides::class,
        SequentialOrderNumbers::class,
        UrlCoupons::class,
        AuthenticationAPI::class,
        CartRecoveryEmails::class,
        Commerce::class,
        CommerceBackfill::class,
        CommerceCustomerPush::class,
        CommercePolling::class,
        CommerceWebhooks::class,
        CostOfGoods::class,
        Interceptors::class,
        GoogleAnalytics::class,
        EmailNotifications::class,
        Marketplaces::class,
        Notices::class,
        PluginControls::class,
        Shipping::class,
        API::class,
        GoDaddyPayments::class,
        Worldpay::class,
        WordPressSso::class,
        ExternalDomainControls::class,
        Webhooks::class,
    ];

    /**
     * Performs actions that this contract should do just after configuration is loaded.
     */
    public function onConfigurationLoaded() : void
    {
        parent::onConfigurationLoaded();

        // skip in CLI mode.
        if (! WordPressRepository::isCliMode()) {
            $this->loadTextDomains();
        }
    }

    /**
     * Loads the plugin's associated text domains.
     */
    protected function loadTextDomains() : void
    {
        $coreDir = plugin_basename(dirname(__DIR__));

        load_plugin_textdomain('mwc-core', false, $coreDir.'/languages');
        load_plugin_textdomain('mwc-common', false, $coreDir.'/vendor/godaddy/mwc-common/languages');
    }

    /**
     * Gets configuration values.
     *
     * @return array
     */
    protected function getConfigurationValues() : array
    {
        return array_merge(parent::getConfigurationValues(), [
            'version'    => '4.6.2',
            'plugin_dir' => dirname(__DIR__),
            'plugin_url' => plugin_dir_url(__DIR__),
        ]);
    }

    /**
     * Instantiates the plugin specific classes.
     *
     * @throws ComponentLoadFailedException|Exception
     */
    protected function instantiatePluginClasses() : void
    {
        static::maybeLoadComponent(ConfigurationLoader::class);

        parent::instantiatePluginClasses();
    }
}
