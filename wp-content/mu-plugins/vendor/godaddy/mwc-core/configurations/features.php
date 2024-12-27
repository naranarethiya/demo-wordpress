<?php

use GoDaddy\WordPress\MWC\Common\Features\AbstractFeature;
use GoDaddy\WordPress\MWC\Common\HostingPlans\Enums\HostingPlanNamesEnum;
use GoDaddy\WordPress\MWC\Core\Auth\Sso\WordPress\WordPressSso;
use GoDaddy\WordPress\MWC\Core\Configuration\CartRecoveryEmailsFeatureRuntimeConfiguration;
use GoDaddy\WordPress\MWC\Core\Features\Assistant\Assistant;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\CartRecoveryEmails;
use GoDaddy\WordPress\MWC\Core\Features\Categories;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Backfill\CommerceBackfill;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Backfill\Jobs\BackfillProductCategoriesJob;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Backfill\Jobs\BackfillProductsJob;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Polling\RemoteCategoriesPollingProcessor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Polling\RemoteProductsPollingProcessor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Webhooks\Handlers\CategoryCreatedWebhookHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Webhooks\Handlers\CategoryDeletedWebhookHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Webhooks\Handlers\CategoryUpdatedWebhookHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Webhooks\Handlers\ProductCreatedWebhookHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Webhooks\Handlers\ProductDeletedWebhookHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Webhooks\Handlers\ProductUpdatedWebhookHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\CommerceCustomerPush;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\CustomersIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\InventoryIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\LocationsIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\OrdersIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Polling\CommercePolling;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\CommerceWebhooks;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Configuration\CommerceWebhooksRuntimeConfiguration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Enums\CommerceWebhookEventTypes;
use GoDaddy\WordPress\MWC\Core\Features\CostOfGoods\CostOfGoods;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\EmailNotifications;
use GoDaddy\WordPress\MWC\Core\Features\ExternalDomainControls\ExternalDomainControls;
use GoDaddy\WordPress\MWC\Core\Features\GiftCertificates\GiftCertificates;
use GoDaddy\WordPress\MWC\Core\Features\GoogleAnalytics\GoogleAnalytics;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Marketplaces;
use GoDaddy\WordPress\MWC\Core\Features\Onboarding\Dashboard;
use GoDaddy\WordPress\MWC\Core\Features\Onboarding\Onboarding;
use GoDaddy\WordPress\MWC\Core\Features\PluginControls\PluginControls;
use GoDaddy\WordPress\MWC\Core\Features\SequentialOrderNumbers\SequentialOrderNumbers;
use GoDaddy\WordPress\MWC\Core\Features\ShipmentTracking\ShipmentTracking;
use GoDaddy\WordPress\MWC\Core\Features\Shipping\Shipping;
use GoDaddy\WordPress\MWC\Core\Features\Stripe\Stripe;
use GoDaddy\WordPress\MWC\Core\Features\UrlCoupons\UrlCoupons;

$mwcEcommerceAllowedHostingPlans = HostingPlanNamesEnum::getAllEcommercePlanNames();

$allMwcsHostingPlans = HostingPlanNamesEnum::getAllMwcsPlanNames();

return [
    /*
     *--------------------------------------------------------------------------
     * Features Settings
     *--------------------------------------------------------------------------
     *
     * The configurations below are used to determine whether a feature is available or not.
     * - If a feature flag exists for a given feature in AWS Evidently, it will override the enabled key.
     * - Order of precedence: overrides.disabled, overrides.enabled, feature flag (from AWS Evidently API), enabled.
     *
     * Descriptive information (name, urls, etc.) is used to display the native features on the WooCommerce > Extensions page.
     */
    'apple_pay'  => ! (defined('DISABLE_MWC_APPLE_PAY') && DISABLE_MWC_APPLE_PAY),
    'google_pay' => ! (defined('DISABLE_MWC_GOOGLE_PAY') && DISABLE_MWC_GOOGLE_PAY),
    'bopit'      => ! (defined('DISABLE_MWC_BOPIT') && DISABLE_MWC_BOPIT),
    'assistant'  => [
        'enabled'   => true,
        'overrides' => [
            'enabled'  => defined('ENABLE_MWC_AI_ASSISTANT') && ENABLE_MWC_AI_ASSISTANT,
            'disabled' => defined('DISABLE_MWC_AI_ASSISTANT') && DISABLE_MWC_AI_ASSISTANT,
        ],
        'className' => Assistant::class,
    ],
    'cost_of_goods' => [
        'name'        => function_exists('__') ? __('Cost of goods', 'mwc-core') : 'Cost of goods',
        'description' => function_exists('__') ? sprintf(
            /* translators: Placeholders: %1$s - <a> tag for the plugin link, %2$s - </a> tag */
            __('Track profit and cost of goods for your store. Generate profit reports by date, product, category, and more. This feature replaces the %1$sCost of Goods%2$s plugin.', 'mwc-core'),
            '<a href="https://woocommerce.com/products/woocommerce-cost-of-goods/" target="_blank">', '</a>'
        ) : '',
        'documentation_url' => 'https://godaddy.com/help/40874',
        'settings_url'      => function_exists('admin_url') ? admin_url('admin.php?page=wc-settings&tab=orders') : '',
        'categories'        => [
            Categories::StoreManagement,
        ],
        'enabled'                         => true,
        'allowedHostingPlans'             => $mwcEcommerceAllowedHostingPlans,
        'requiredPlugins'                 => ['woocommerce'],
        'shouldDisableAccountRestriction' => AbstractFeature::DISABLE_ACCOUNT_RESTRICTION_ALWAYS,
        'overrides'                       => [
            'enabled'  => defined('ENABLE_MWC_COST_OF_GOODS') && ENABLE_MWC_COST_OF_GOODS,
            'disabled' => defined('DISABLE_MWC_COST_OF_GOODS') && DISABLE_MWC_COST_OF_GOODS,
        ],
        'className' => CostOfGoods::class,
    ],
    'email_deliverability' => ! (defined('DISABLE_MWC_EMAIL_DELIVERABILITY') && DISABLE_MWC_EMAIL_DELIVERABILITY),
    'email_notifications'  => [
        'name'        => function_exists('__') ? __('Ecommerce emails', 'mwc-core') : 'Ecommerce emails',
        'description' => function_exists('__') ? sprintf(
            /* translators: Placeholders: %1$s - <a> tag for the plugin link, %2$s - </a> tag */
            __('Customize your emails to reflect your brand and increase customer loyalty. This feature replaces the %1$sWooCommerce Email Customizer%2$s plugin.', 'mwc-core'),
            '<a href="https://woocommerce.com/products/woocommerce-email-customizer/" target="_blank">', '</a>'
        ) : '',
        'documentation_url' => 'https://godaddy.com/help/40929',
        'settings_url'      => function_exists('admin_url') ? admin_url('admin.php?page=gd-email-notifications&tab=settings') : '',
        'categories'        => [
            Categories::Marketing,
        ],
        'enabled'                         => true,
        'allowedHostingPlans'             => $mwcEcommerceAllowedHostingPlans,
        'requiredPlugins'                 => ['woocommerce'],
        'shouldDisableAccountRestriction' => AbstractFeature::DISABLE_ACCOUNT_RESTRICTION_WITH_OVERRIDE,
        'overrides'                       => [
            'enabled'  => defined('ENABLE_MWC_EMAIL_NOTIFICATIONS') && ENABLE_MWC_EMAIL_NOTIFICATIONS,
            'disabled' => defined('DISABLE_MWC_EMAIL_NOTIFICATIONS') && DISABLE_MWC_EMAIL_NOTIFICATIONS,
        ],
        'className' => EmailNotifications::class,
    ],
    'godaddy_payments' => [
        'disabled'           => defined('DISABLE_GODADDY_PAYMENTS') && DISABLE_GODADDY_PAYMENTS,
        'supportedCountries' => defined('DISABLE_GODADDY_PAYMENTS_CANADA') && DISABLE_GODADDY_PAYMENTS_CANADA ? [
            'US' => ['USD'],
        ] : [
            'US' => ['USD'],
            'CA' => ['CAD'],
        ],
    ],
    'google_analytics' => [
        'name'        => function_exists('__') ? __('Google analytics', 'mwc-core') : 'Google analytics',
        'description' => function_exists('__') ? sprintf(
            /* translators: Placeholders: %1$s and %3$s - <a> tag for the plugin link, %2$s and %4$s - </a> tag */
            __('Track advanced eCommerce events and more with Google Analytics. This feature replaces the %1$sGoogle Analytics%2$s and %3$sGoogle Analytics Pro%4$s plugins.', 'mwc-core'),
            '<a href="https://woocommerce.com/products/woocommerce-google-analytics/" target="_blank">', '</a>',
            '<a href="https://woocommerce.com/products/woocommerce-google-analytics-pro/" target="_blank">', '</a>'
        ) : '',
        'documentation_url' => 'https://godaddy.com/help/40882',
        'settings_url'      => function_exists('admin_url') ? admin_url('admin.php?page=wc-settings&tab=integration&section=google_analytics_pro') : '',
        'categories'        => [
            Categories::Marketing,
        ],
        'enabled'                         => true,
        'allowedHostingPlans'             => $mwcEcommerceAllowedHostingPlans,
        'requiredPlugins'                 => ['woocommerce'],
        'shouldDisableAccountRestriction' => AbstractFeature::DISABLE_ACCOUNT_RESTRICTION_ALWAYS,
        'overrides'                       => [
            'enabled'  => defined('ENABLE_MWC_GOOGLE_ANALYTICS') && ENABLE_MWC_GOOGLE_ANALYTICS,
            'disabled' => defined('DISABLE_MWC_GOOGLE_ANALYTICS') && DISABLE_MWC_GOOGLE_ANALYTICS,
        ],
        'className' => GoogleAnalytics::class,
    ],
    'onboarding' => [
        'enabled'                         => true,
        'allowedHostingPlans'             => [],
        'requiredPlugins'                 => ['woocommerce'],
        'shouldDisableAccountRestriction' => AbstractFeature::DISABLE_ACCOUNT_RESTRICTION_WITH_OVERRIDE,
        'overrides'                       => [
            'enabled'  => defined('ENABLE_MWC_ONBOARDING') && ENABLE_MWC_ONBOARDING,
            'disabled' => defined('DISABLE_MWC_ONBOARDING') && DISABLE_MWC_ONBOARDING,
        ],
        'className' => Onboarding::class,
    ],
    'sequential_order_numbers' => [
        'name'        => function_exists('__') ? __('Sequential order numbers', 'mwc-core') : 'Sequential order numbers',
        'description' => function_exists('__') ? sprintf(
            /* translators: Placeholders: %1$s - <a> tag for the plugin link, %2$s - </a> tag */
            __('Format order numbers, change your starting number, and differentiate free orders. This feature replaces the %1$sSequential Order Numbers Pro%2$s plugin.', 'mwc-core'),
            '<a href="https://woocommerce.com/products/sequential-order-numbers-pro/" target="_blank">', '</a>'
        ) : '',
        'documentation_url' => 'https://godaddy.com/help/40712',
        'settings_url'      => function_exists('admin_url') ? admin_url('admin.php?page=wc-settings&tab=orders') : '',
        'categories'        => [
            Categories::StoreManagement,
        ],
        'enabled'                         => true,
        'allowedHostingPlans'             => $mwcEcommerceAllowedHostingPlans,
        'requiredPlugins'                 => ['woocommerce'],
        'shouldDisableAccountRestriction' => AbstractFeature::DISABLE_ACCOUNT_RESTRICTION_ALWAYS,
        'overrides'                       => [
            'enabled'  => defined('ENABLE_MWC_SEQUENTIAL_ORDER_NUMBERS') && ENABLE_MWC_SEQUENTIAL_ORDER_NUMBERS,
            'disabled' => defined('DISABLE_MWC_SEQUENTIAL_ORDER_NUMBERS') && DISABLE_MWC_SEQUENTIAL_ORDER_NUMBERS,
        ],
        'className' => SequentialOrderNumbers::class,
    ],
    'shipment_tracking' => [
        'name'        => function_exists('__') ? __('Shipment tracking', 'mwc-core') : 'Shipment tracking',
        'description' => function_exists('__') ? sprintf(
            /* translators: Placeholders: %1$s - <a> tag for the plugin link, %2$s - </a> tag */
            __('Share shipment tracking information with your customers. Open one of your Processing orders to get started. This feature replaces the %1$sShipment Tracking%2$s plugin.', 'mwc-core'),
            '<a href="https://woocommerce.com/products/shipment-tracking/" target="_blank">', '</a>'
        ) : '',
        'documentation_url' => 'https://godaddy.com/help/40631',
        'settings_url'      => '',
        'categories'        => [
            Categories::Shipping,
        ],
        'enabled'   => get_option('mwc_shipment_tracking_active', 'yes') === 'yes',
        'className' => ShipmentTracking::class,
    ],
    'url_coupons' => [
        'name'        => function_exists('__') ? __('Discount links', 'mwc-core') : 'Discount links',
        'description' => function_exists('__') ? sprintf(
            /* translators: Placeholders: %1$s - <a> tag for the plugin link, %2$s - </a> tag */
            __('Share discount links with your customers and add coupons from ads, email campaigns, or social links. Create or edit a coupon to get started. This feature replaces the %1$sURL Coupons%2$s plugin.', 'mwc-core'),
            '<a href="https://woocommerce.com/products/url-coupons/" target="_blank">', '</a>'
        ) : '',
        'documentation_url' => 'https://godaddy.com/help/40840',
        'categories'        => [
            Categories::Marketing,
        ],
        'enabled'                         => true,
        'allowedHostingPlans'             => $mwcEcommerceAllowedHostingPlans,
        'requiredPlugins'                 => ['woocommerce'],
        'shouldDisableAccountRestriction' => AbstractFeature::DISABLE_ACCOUNT_RESTRICTION_ALWAYS,
        'overrides'                       => [
            'enabled'  => defined('ENABLE_MWC_URL_COUPONS') && ENABLE_MWC_URL_COUPONS,
            'disabled' => defined('DISABLE_MWC_URL_COUPONS') && DISABLE_MWC_URL_COUPONS,
        ],
        'className' => UrlCoupons::class,
    ],
    'gift_certificates' => [
        'name'        => function_exists('__') ? __('Gift certificates', 'mwc-core') : 'Gift certificates',
        'description' => function_exists('__') ? sprintf(
            /* translators: Placeholders: %1$s - <a> tag for the plugin link, %2$s - </a> tag */
            __('Create custom gift certificates that your customers can purchase and send to their friends and family. This feature replaces the %1$sPDF Product Vouchers%2$s plugin.', 'mwc-core'),
            '<a href="https://woocommerce.com/products/pdf-product-vouchers/" target="_blank">', '</a>'
        ) : '',
        'documentation_url' => 'https://www.godaddy.com/help/40294',
        'settings_url'      => function_exists('admin_url') ? admin_url('edit.php?post_type=wc_voucher') : '',
        'categories'        => [
            Categories::Merchandising,
            Categories::ProductType,
        ],
        'enabled'                         => true,
        'allowedHostingPlans'             => $mwcEcommerceAllowedHostingPlans,
        'requiredPlugins'                 => ['woocommerce'],
        'shouldDisableAccountRestriction' => AbstractFeature::DISABLE_ACCOUNT_RESTRICTION_NEVER,
        'overrides'                       => [
            'enabled'  => defined('ENABLE_MWC_GIFT_CERTIFICATES') && ENABLE_MWC_GIFT_CERTIFICATES,
            'disabled' => defined('DISABLE_MWC_GIFT_CERTIFICATES') && DISABLE_MWC_GIFT_CERTIFICATES,
        ],
        'className' => GiftCertificates::class,
    ],
    'onboarding_dashboard' => [
        'enabled'                         => true,
        'allowedHostingPlans'             => [],
        'requiredPlugins'                 => ['woocommerce'],
        'shouldDisableAccountRestriction' => AbstractFeature::DISABLE_ACCOUNT_RESTRICTION_WITH_OVERRIDE,
        'overrides'                       => [
            'enabled'  => defined('ENABLE_MWC_ONBOARDING_DASHBOARD') && ENABLE_MWC_ONBOARDING_DASHBOARD,
            'disabled' => defined('DISABLE_MWC_ONBOARDING_DASHBOARD') && DISABLE_MWC_ONBOARDING_DASHBOARD,
        ],
        'className' => Dashboard::class,
    ],
    'bopit_sync' => [
        'enabled' => ! (defined('DISABLE_MWC_BOPIT_SYNC') && DISABLE_MWC_BOPIT_SYNC),
    ],
    'cart_recovery_emails' => [
        'name'              => function_exists('__') ? __('Abandoned checkout reminders', 'mwc-core') : 'Abandoned checkout reminders',
        'description'       => function_exists('__') ? __('Automatically track cart activity and send an email reminder to recover lost revenue.', 'mwc-core') : '',
        'documentation_url' => 'https://godaddy.com/help/41079',
        'settings_url'      => function_exists('admin_url') ? admin_url('admin.php?page=gd-email-notifications&tab=emails&category=cart_recovery') : '',
        'categories'        => [
            Categories::CartCheckout,
            Categories::Marketing,
        ],
        'enabled'                  => true,
        'requiredFeatures'         => EmailNotifications::class,
        'expired_cart_in_seconds'  => 14 * 24 * 60 * 60, // 14 days
        'expiring_cart_in_seconds' => 13 * 24 * 60 * 60, // 13 days
        'send_wait_period'         => 5 * 24 * 60 * 60, // 5 days
        'overrides'                => [
            'enabled'  => defined('ENABLE_MWC_CART_RECOVERY_EMAILS') && ENABLE_MWC_CART_RECOVERY_EMAILS,
            'disabled' => defined('DISABLE_MWC_CART_RECOVERY_EMAILS') && DISABLE_MWC_CART_RECOVERY_EMAILS,
        ],
        'className'             => CartRecoveryEmails::class,
        'runtime_configuration' => CartRecoveryEmailsFeatureRuntimeConfiguration::class,
        'isDelayReadOnly'       => false,
    ],
    'gdp_by_default' => [
        'enabled' => ! (defined('DISABLE_GDP_BY_DEFAULT') && DISABLE_GDP_BY_DEFAULT),
    ],
    'stripe' => [
        'enabled'   => true,
        'overrides' => [
            'disabled' => defined('DISABLE_MWC_STRIPE') && DISABLE_MWC_STRIPE,
        ],
        'requiredPlugins'   => ['woocommerce'],
        'name'              => function_exists('__') ? __('Stripe', 'mwc-core') : 'Stripe',
        'description'       => function_exists('__') ? __('Accept credit card payments using Stripe.', 'mwc-core') : '',
        'documentation_url' => 'https://stripe.com/pricing',
        'settings_url'      => function_exists('admin_url') ? admin_url('admin.php?page=wc-settings&tab=checkout&section=stripe') : '',
        'categories'        => [
            Categories::Payments,
        ],
        'allowedHostingPlans' => [],
        'className'           => Stripe::class,
    ],
    'marketplaces' => [
        'name'              => function_exists('__') ? __('Marketplaces', 'mwc-core') : 'Marketplaces',
        'description'       => function_exists('__') ? __('Sell to millions of customers from one place. Offer your products everywhere, from Amazon to Instagram, all from your own online store.', 'mwc-core') : '',
        'documentation_url' => 'https://godaddy.com/help/a-41221',
        'settings_url'      => function_exists('admin_url') ? admin_url('admin.php?page=gd-marketplaces') : '',
        'categories'        => [
            Categories::Marketing,
            Categories::Merchandising,
            Categories::StoreManagement,
        ],
        'enabled'   => true,
        'overrides' => [
            'enabled'  => defined('ENABLE_MWC_MARKETPLACES') && ENABLE_MWC_MARKETPLACES,
            'disabled' => defined('DISABLE_MWC_MARKETPLACES') && DISABLE_MWC_MARKETPLACES,
        ],
        'requiredPlugins' => ['woocommerce'],
        // The Marketplaces feature is not available in CA markets yet -- {wvega 2023-07-07}
        'allowedHostingPlans' => array_merge(HostingPlanNamesEnum::getDefaultMwcsPlanNames(), HostingPlanNamesEnum::getAllWorldpayPlanNames()),
        'className'           => Marketplaces::class,
    ],
    'shipping' => [
        'enabled'           => true,
        'name'              => 'Shipping',
        'description'       => function_exists('__') ? __('Take order fulfillment to the next level! Get live shipping rates from your favourite providers, print labels in one click, and automate shipment tracking and customer email notifications.', 'mwc-core') : '',
        'documentation_url' => 'https://godaddy.com/help/a-41210',
        'settings_url'      => '',
        'categories'        => [
            Categories::Shipping,
        ],
        'allowedHostingPlans' => $allMwcsHostingPlans,
        'overrides'           => [
            'enabled'  => defined('ENABLE_MWC_SHIPPING') && ENABLE_MWC_SHIPPING,
            'disabled' => defined('DISABLE_MWC_SHIPPING') && DISABLE_MWC_SHIPPING,
        ],
        'requiredPlugins' => ['woocommerce'],
        'className'       => Shipping::class,
    ],
    'worldpay' => [
        'enabled' => true,

        // Note: MWP hosted sites do not currently have a WorldPay FPID,
        // this will cause the feature to fail to load even though the plan may enable it.
        // {@see WorldPlay::shouldLoad()}
        'allowedHostingPlans' => $allMwcsHostingPlans,
        'hqUrl'               => 'https://poynt.godaddy.com/',
        'baseMenuUrl'         => 'https://commerce.godaddy.com/',
        'useNewUrls'          => true,
        'overrides'           => [
            'enabled'  => defined('ENABLE_MWC_WORLDPAY') && ENABLE_MWC_WORLDPAY,
            'disabled' => defined('DISABLE_MWC_WORLDPAY') && DISABLE_MWC_WORLDPAY,
        ],
    ],
    'commerce' => [
        'enabled'             => true,
        'allowedHostingPlans' => $allMwcsHostingPlans,
        'overrides'           => [
            'enabled'  => defined('ENABLE_COMMERCE_INTEGRATION') && ENABLE_COMMERCE_INTEGRATION,
            'disabled' => defined('DISABLE_COMMERCE_INTEGRATION') && DISABLE_COMMERCE_INTEGRATION,
        ],
        'requiredPlugins' => ['woocommerce'],
        'className'       => Commerce::class,
        'integrations'    => [
            CatalogIntegration::NAME => [
                'className' => CatalogIntegration::class,
                'enabled'   => true,
                'overrides' => [
                    'enabled'  => defined('ENABLE_MWC_COMMERCE_CATALOG_INTEGRATION') && ENABLE_MWC_COMMERCE_CATALOG_INTEGRATION,
                    'disabled' => defined('DISABLE_MWC_COMMERCE_CATALOG_INTEGRATION') && DISABLE_MWC_COMMERCE_CATALOG_INTEGRATION,
                ],
                'capabilities' => [
                    Commerce::CAPABILITY_READ                    => ! (defined('DISABLE_MWC_COMMERCE_CATALOG_READ') && DISABLE_MWC_COMMERCE_CATALOG_READ),
                    Commerce::CAPABILITY_WRITE                   => ! (defined('DISABLE_MWC_COMMERCE_CATALOG_WRITE') && DISABLE_MWC_COMMERCE_CATALOG_WRITE),
                    Commerce::CAPABILITY_EVENTS                  => ! (defined('DISABLE_MWC_COMMERCE_CATALOG_EVENTS') && DISABLE_MWC_COMMERCE_CATALOG_EVENTS),
                    Commerce::CAPABILITY_DETECT_UPSTREAM_CHANGES => ! (defined('DISABLE_MWC_COMMERCE_CATALOG_DETECT_UPSTREAM_CHANGES') && DISABLE_MWC_COMMERCE_CATALOG_DETECT_UPSTREAM_CHANGES),
                ],
                'webhooks' => [
                    // Event types we should subscribe to *if* the integration is enabled.
                    'eventTypes' => [
                        CommerceWebhookEventTypes::CategoryCreated => CategoryCreatedWebhookHandler::class,
                        CommerceWebhookEventTypes::CategoryUpdated => CategoryUpdatedWebhookHandler::class,
                        CommerceWebhookEventTypes::CategoryDeleted => CategoryDeletedWebhookHandler::class,
                        CommerceWebhookEventTypes::ProductCreated  => ProductCreatedWebhookHandler::class,
                        CommerceWebhookEventTypes::ProductUpdated  => ProductUpdatedWebhookHandler::class,
                        CommerceWebhookEventTypes::ProductDeleted  => ProductDeletedWebhookHandler::class,
                    ],
                ],
            ],
            CustomersIntegration::NAME => [
                'className' => CustomersIntegration::class,
                'enabled'   => true,
                'overrides' => [
                    'enabled'  => defined('ENABLE_MWC_COMMERCE_CUSTOMERS_INTEGRATION') && ENABLE_MWC_COMMERCE_CUSTOMERS_INTEGRATION,
                    'disabled' => defined('DISABLE_MWC_COMMERCE_CUSTOMERS_INTEGRATION') && DISABLE_MWC_COMMERCE_CUSTOMERS_INTEGRATION,
                ],
                'capabilities' => [
                    Commerce::CAPABILITY_READ  => ! (defined('DISABLE_MWC_COMMERCE_CUSTOMERS_READ') && DISABLE_MWC_COMMERCE_CUSTOMERS_READ),
                    Commerce::CAPABILITY_WRITE => ! (defined('DISABLE_MWC_COMMERCE_CUSTOMERS_WRITE') && DISABLE_MWC_COMMERCE_CUSTOMERS_WRITE),
                ],
            ],
            InventoryIntegration::NAME => [
                'className'        => InventoryIntegration::class,
                'requiredFeatures' => CatalogIntegration::class,
                'enabled'          => true,
                'overrides'        => [
                    'enabled'  => defined('ENABLE_MWC_COMMERCE_INVENTORY_INTEGRATION') && ENABLE_MWC_COMMERCE_INVENTORY_INTEGRATION,
                    'disabled' => defined('DISABLE_MWC_COMMERCE_INVENTORY_INTEGRATION') && DISABLE_MWC_COMMERCE_INVENTORY_INTEGRATION,
                ],
                'capabilities' => [
                    Commerce::CAPABILITY_READ  => ! (defined('DISABLE_MWC_COMMERCE_INVENTORY_READ') && DISABLE_MWC_COMMERCE_INVENTORY_READ),
                    Commerce::CAPABILITY_WRITE => ! (defined('DISABLE_MWC_COMMERCE_INVENTORY_WRITE') && DISABLE_MWC_COMMERCE_INVENTORY_WRITE),
                ],
            ],
            LocationsIntegration::NAME => [
                'className'        => LocationsIntegration::class,
                'requiredFeatures' => CatalogIntegration::class,
                'enabled'          => true,
                'overrides'        => [
                    'enabled'  => defined('ENABLE_MWC_COMMERCE_LOCATIONS_INTEGRATION') && ENABLE_MWC_COMMERCE_LOCATIONS_INTEGRATION,
                    'disabled' => defined('DISABLE_MWC_COMMERCE_LOCATIONS_INTEGRATION') && DISABLE_MWC_COMMERCE_LOCATIONS_INTEGRATION,
                ],
                'capabilities' => [
                    Commerce::CAPABILITY_READ  => ! (defined('DISABLE_MWC_COMMERCE_LOCATIONS_READ') && DISABLE_MWC_COMMERCE_LOCATIONS_READ),
                    Commerce::CAPABILITY_WRITE => ! (defined('DISABLE_MWC_COMMERCE_LOCATIONS_WRITE') && DISABLE_MWC_COMMERCE_LOCATIONS_WRITE),
                ],
            ],
            OrdersIntegration::NAME => [
                'className' => OrdersIntegration::class,
                'enabled'   => true,
                'overrides' => [
                    'enabled'  => defined('ENABLE_MWC_COMMERCE_ORDERS_INTEGRATION') && ENABLE_MWC_COMMERCE_ORDERS_INTEGRATION,
                    'disabled' => defined('DISABLE_MWC_COMMERCE_ORDERS_INTEGRATION') && DISABLE_MWC_COMMERCE_ORDERS_INTEGRATION,
                ],
                'capabilities' => [
                    Commerce::CAPABILITY_READ  => ! (defined('DISABLE_MWC_COMMERCE_ORDERS_READ') && DISABLE_MWC_COMMERCE_ORDERS_READ),
                    Commerce::CAPABILITY_WRITE => ! (defined('DISABLE_MWC_COMMERCE_ORDERS_WRITE') && DISABLE_MWC_COMMERCE_ORDERS_WRITE),
                ],
            ],
        ],
        'capabilities' => [
            Commerce::CAPABILITY_READ  => ! (defined('DISABLE_MWC_COMMERCE_READ') && DISABLE_MWC_COMMERCE_READ),
            Commerce::CAPABILITY_WRITE => ! (defined('DISABLE_MWC_COMMERCE_WRITE') && DISABLE_MWC_COMMERCE_WRITE),
        ],
    ],
    'commerce_customer_push' => [
        'enabled'   => false,
        'overrides' => [
            'enabled'  => defined('ENABLE_COMMERCE_CUSTOMER_PUSH') && ENABLE_COMMERCE_CUSTOMER_PUSH,
            'disabled' => defined('DISABLE_COMMERCE_CUSTOMER_PUSH') && DISABLE_COMMERCE_CUSTOMER_PUSH,
        ],
        'requiredFeatures' => [Commerce::class],
        'className'        => CommerceCustomerPush::class,
    ],
    'commerce_backfill' => [
        'enabled'             => true,
        'allowedHostingPlans' => $allMwcsHostingPlans,
        'overrides'           => [
            'enabled'  => defined('ENABLE_MWC_COMMERCE_BACKFILL_INTEGRATION') && ENABLE_MWC_COMMERCE_BACKFILL_INTEGRATION,
            'disabled' => defined('DISABLE_MWC_COMMERCE_BACKFILL_INTEGRATION') && DISABLE_MWC_COMMERCE_BACKFILL_INTEGRATION,
        ],
        'requiredFeatures' => [Commerce::class],
        'className'        => CommerceBackfill::class,
        'jobs'             => [
            // backfill jobs listed in the order they should be run (with dependencies in mind)
            // see queue.jobs config for registered jobs and their settings
            BackfillProductCategoriesJob::class,
            BackfillProductsJob::class,
        ],
    ],
    'commerce_polling' => [
        'enabled'   => true,
        'overrides' => [
            // @deprecated: ENABLE_MWC_COMMERCE_POLLING_INTEGRATION is deprecated and will be removed in the future, use ENABLE_COMMERCE_POLLING instead.
            'enabled' => (defined('ENABLE_MWC_COMMERCE_POLLING_INTEGRATION') && ENABLE_MWC_COMMERCE_POLLING_INTEGRATION) || (defined('ENABLE_COMMERCE_POLLING') && ENABLE_COMMERCE_POLLING),
            // @deprecated: DISABLE_MWC_COMMERCE_POLLING_INTEGRATION is deprecated and will be removed in the future, use DISABLE_COMMERCE_POLLING instead.
            'disabled' => defined('DISABLE_MWC_COMMERCE_BACKFILL_INTEGRATION') && DISABLE_MWC_COMMERCE_BACKFILL_INTEGRATION || defined('DISABLE_COMMERCE_POLLING') && DISABLE_COMMERCE_POLLING,
        ],
        'requiredFeatures'       => [Commerce::class],
        'className'              => CommercePolling::class,
        'supervisorDateInterval' => 'PT2M', // interval at which the supervisor should run -- passed to DateInterval constructor @link https://www.php.net/manual/en/dateinterval.construct.php
        'jobs'                   => [
            RemoteProductsPollingProcessor::NAME => [
                'enabled'          => ! (defined('DISABLE_MWC_COMMERCE_CATALOG_REMOTE_PRODUCTS_POLLING') && DISABLE_MWC_COMMERCE_CATALOG_REMOTE_PRODUCTS_POLLING),
                'requiredFeatures' => [CatalogIntegration::class],
                'jobProcessor'     => RemoteProductsPollingProcessor::class,
                'jobDateInterval'  => [
                    'default'  => 'PT2M', // note that 2 minutes will be the fallback value in case the configuration value is not set or is invalid
                    'override' => defined('MWC_COMMERCE_CATALOG_REMOTE_PRODUCTS_POLLING_DATE_INTERVAL') ? (string) MWC_COMMERCE_CATALOG_REMOTE_PRODUCTS_POLLING_DATE_INTERVAL : null, // empty value means no override
                ],
                'pollingRequestPageSize' => 50,
            ],
            RemoteCategoriesPollingProcessor::NAME => [
                'enabled'          => ! (defined('DISABLE_MWC_COMMERCE_CATALOG_REMOTE_CATEGORIES_POLLING') && DISABLE_MWC_COMMERCE_CATALOG_REMOTE_CATEGORIES_POLLING),
                'requiredFeatures' => [CatalogIntegration::class],
                'jobProcessor'     => RemoteCategoriesPollingProcessor::class,
                'jobDateInterval'  => [
                    'default'  => 'PT2M', // note that 2 minutes will be the fallback value in case the configuration value is not set or is invalid
                    'override' => defined('MWC_COMMERCE_CATALOG_REMOTE_CATEGORIES_POLLING_DATE_INTERVAL') ? (string) MWC_COMMERCE_CATALOG_REMOTE_CATEGORIES_POLLING_DATE_INTERVAL : null, // empty value means no override
                ],
                'pollingRequestPageSize' => 50,
            ],
        ],
    ],
    'commerce_webhooks' => [
        'enabled'   => true,
        'overrides' => [
            'enabled'  => (defined('ENABLE_COMMERCE_WEBHOOKS') && ENABLE_COMMERCE_WEBHOOKS),
            'disabled' => defined('DISABLE_COMMERCE_WEBHOOKS') && DISABLE_COMMERCE_WEBHOOKS,
        ],
        'requiredFeatures' => [Commerce::class],
        'className'        => CommerceWebhooks::class,
        /*
         * Webhook event types to subscribe to. The below array is events that should be subscribed to 100% of the time.
         * This will get merged with integration-specific eventTypes via {@see CommerceWebhooksRuntimeConfiguration}
         * Do not reference this config directly; always use the runtime config class.
         */
        'eventTypes' => [],
    ],
    'wordpress_sso' => [
        'enabled'             => true,
        'allowedHostingPlans' => $allMwcsHostingPlans,
        'className'           => WordPressSso::class,
        /*
         *--------------------------------------------------------------------------
         * Care Agent Account Settings
         *--------------------------------------------------------------------------
         */
        'care' => [
            'autoDeleteInterval' => 'PT24H', // after how long the care agent account should be deleted -- default is 24 hours
            'name'               => 'GoDaddy Care Agent',
            'usernamePrefix'     => 'godaddycare', // username prefix -- final username will be appended with a random string
            'email'              => 'commerce@services.godaddy.com',
        ],
    ],
    'plugin_controls' => [
        'enabled'             => true,
        'allowedHostingPlans' => $allMwcsHostingPlans,
        'className'           => PluginControls::class,
    ],
    'external_domain_controls' => [
        'enabled'             => true,
        'allowedHostingPlans' => $allMwcsHostingPlans,
        'className'           => ExternalDomainControls::class,
        /*
         *--------------------------------------------------------------------------
         * Domain Attach Flow
         *--------------------------------------------------------------------------
         *
         * enable & configure the domain attachment flow notices in Settings > General and Settings > Reading
         *
         */
        'domainAttachFlow' => [
            'showNotices'   => true,
            'attachmentUrl' => 'https://mwcstores.godaddy.com/overview/?initialAddDomainStep=START_STEP',
        ],
    ],
];
