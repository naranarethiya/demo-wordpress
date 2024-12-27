<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces;

use Exception;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notices;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\DelayedInstantiationComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentClassesNotDefinedException;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentLoadFailedException;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Features\AbstractFeature;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Url;
use GoDaddy\WordPress\MWC\Common\Http\Url\Exceptions\InvalidUrlException;
use GoDaddy\WordPress\MWC\Common\Http\Url\Exceptions\InvalidUrlSchemeException;
use GoDaddy\WordPress\MWC\Common\Http\Url\QueryParameters;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;
use GoDaddy\WordPress\MWC\Common\Stores\Repositories\AbstractStoreRepository;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers\ChannelWebhookSubscriber;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers\ListingWebhookSubscriber;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers\OrderWebhookSubscriber;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Google\Interceptors\GoogleProductIdInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Handlers\MerchantAccountLevelDataHandler;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Handlers\MerchantProvisioningHandler;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Handlers\SellbritePluginHandler;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Interceptors\CreateDraftListingAjaxInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Interceptors\DuplicateProductInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Interceptors\EditProductPageInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Interceptors\EmailInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Interceptors\GoogleVerificationInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Interceptors\MerchantProvisionedOptionInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Interceptors\OrderInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Interceptors\ProductBulkSyncActionInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Interceptors\ProductInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Interceptors\ProductsPageInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Pages\EditOrder\Fields\MarketplacesFields as OrderMarketplacesFields;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Pages\EditProduct\Fields\MarketplacesFields as ProductMarketplacesFields;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Pages\EditProduct\Metaboxes\MarketplacesMetabox;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Pages\MarketplacesPage;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Repositories\OrderRepository;

/**
 * The GoDaddy Marketplaces feature class.
 */
class Marketplaces extends AbstractFeature implements DelayedInstantiationComponentContract
{
    use HasComponentsTrait;

    /** @var class-string<ComponentContract>[] alphabetically ordered list of components to load */
    protected array $componentClasses = [
        ChannelWebhookSubscriber::class,
        CreateDraftListingAjaxInterceptor::class,
        DuplicateProductInterceptor::class,
        EditProductPageInterceptor::class,
        EmailInterceptor::class,
        GoogleProductIdInterceptor::class,
        GoogleVerificationInterceptor::class,
        ListingWebhookSubscriber::class,
        MarketplacesMetabox::class,
        MarketplacesPage::class,
        MerchantAccountLevelDataHandler::class,
        MerchantProvisioningHandler::class,
        MerchantProvisionedOptionInterceptor::class,
        OrderInterceptor::class,
        OrderMarketplacesFields::class,
        OrderWebhookSubscriber::class,
        ProductBulkSyncActionInterceptor::class,
        ProductInterceptor::class,
        ProductMarketplacesFields::class,
        ProductsPageInterceptor::class,
        SellbritePluginHandler::class,
    ];

    /**
     * Determines whether the feature should load.
     *
     * @return bool
     * @throws PlatformRepositoryException
     */
    public static function shouldLoad() : bool
    {
        if (! parent::shouldLoad()) {
            return false;
        }

        if (static::areMinimumSettingsSupported()) {
            static::maybeEnqueueStagingSiteAdminNotice();
            static::enqueueOrderLimitApproachingAdminNotice();
            static::enqueueOrderLimitReachedAdminNotice();

            static::maybeEnqueueUnitsAdminNotice();
            static::maybeEnqueueInventoryAdminNotice();

            return true;
        }

        return false;
    }

    /**
     * Checks if the site settings match the Marketplaces required settings.
     *
     * @return bool
     */
    public static function areSettingsSupported() : bool
    {
        return static::areMinimumSettingsSupported()
            && static::isSupportedWeightUnit()
            && static::isSupportedDimensionUnit()
            && static::isSupportedStockManagement();
    }

    /**
     * Checks if the site setting match the minimum settings required to display the Marketplaces feature.
     *
     * @return bool
     */
    protected static function areMinimumSettingsSupported() : bool
    {
        return static::isSupportedCurrency()
            && static::isSupportedCountry();
    }

    /**
     * Checks if the site currency matches a Marketplaces supported currency.
     *
     * @return bool
     */
    protected static function isSupportedCurrency() : bool
    {
        return 'usd' === strtolower(WooCommerceRepository::getCurrency());
    }

    /**
     * Checks if the site country matches a supported country.
     *
     * @return bool
     */
    protected static function isSupportedCountry() : bool
    {
        return 'us' === strtolower(WooCommerceRepository::getBaseCountry());
    }

    /**
     * Checks if the site weight unit matches a supported weight unit.
     *
     * @return bool
     */
    protected static function isSupportedWeightUnit() : bool
    {
        return ArrayHelper::contains(['lbs'], get_option('woocommerce_weight_unit'));
    }

    /**
     * Checks if the site dimension unit matches a supported dimension unit.
     *
     * @return bool
     */
    protected static function isSupportedDimensionUnit() : bool
    {
        return ArrayHelper::contains(['in'], get_option('woocommerce_dimension_unit'));
    }

    /**
     * Checks if the site stock management settings are supported.
     *
     * @return bool
     */
    protected static function isSupportedStockManagement() : bool
    {
        return 'yes' === get_option('woocommerce_manage_stock');
    }

    /**
     * {@inheritDoc}
     */
    public static function getName() : string
    {
        return 'marketplaces';
    }

    /**
     * Loads the feature.
     *
     * @return void
     * @throws ComponentClassesNotDefinedException|ComponentLoadFailedException
     */
    public function load()
    {
        $this->loadComponents();

        $this->maybeEnqueueNewFeatureAdminNotice();
    }

    /**
     * Schedules the component instantiation.
     *
     * @param $callback
     * @return void
     * @throws Exception
     */
    public static function scheduleInstantiation($callback) : void
    {
        Register::action()
            ->setGroup('wp_loaded')
            ->setHandler($callback)
            ->setPriority(PHP_INT_MAX)
            ->execute();
    }

    /**
     * Gets a URL for the Marketplaces website.
     *
     * @param string $path
     * @return string
     * @throws Exception
     */
    public static function getMarketplacesUrl(string $path) : string
    {
        $pathParts = Url::fromString($path);
        $queryParams = $pathParts->getQueryParameters() ?? new QueryParameters();

        $url = Url::fromString(TypeHelper::string(Configuration::get('marketplaces.website.url'), ''))
              ->setPath($pathParts->getPath());

        if (! $queryParams->has('store_id') && $storeId = static::getStoreId()) {
            $queryParams->add('store_id', $storeId);
        }

        $marketplacesUrl = $url->setQueryParameters($queryParams)
            ->toString();

        // @TODO Remove this preg_replace() on the product_ids[] param once the View Draft Listing path is updated to longer include it {@ajaynes 2023-01-27}
        if ($queryParams->has('product_ids')) {
            return TypeHelper::string(preg_replace('/product_ids%5B[0-9]+%5D/simU', 'product_ids%5B%5D', $marketplacesUrl), $marketplacesUrl);
        } else {
            return $marketplacesUrl;
        }
    }

    /**
     * Get the "sales channel" section base URL.
     * Note: this currently differs from the Marketplaces URL.
     *
     * @param string $path
     * @return string
     * @throws InvalidUrlException
     * @throws InvalidUrlSchemeException
     */
    public static function getSalesChannelsUrl(string $path) : string
    {
        return Url::fromString(TypeHelper::string(Configuration::get('marketplaces.website.salesChannelsUrl'), ''))
            ->setPath($path)
            ->toString();
    }

    /**
     * Get the sales channel "hub" URL.
     *
     * @param string $path
     * @return string
     * @throws InvalidUrlException
     * @throws InvalidUrlSchemeException
     */
    public static function getCommerceHubUrl(string $path) : string
    {
        return Url::fromString(TypeHelper::string(Configuration::get('marketplaces.website.commerceHubUrl'), ''))
            ->setPath($path)
            ->toString();
    }

    /**
     * Displays a dismissible admin notice about the new feature on the new Marketplaces page.
     *
     * @return void
     */
    protected function maybeEnqueueNewFeatureAdminNotice() : void
    {
        $productsPageUrl = SiteRepository::getAdminUrl('edit.php?post_type=product');

        $content = sprintf(
            /* translators: Placeholders: %1$s - opening HTML link tag <a> to products page, %2$s - closing HTML link tag </a> */
            __('Create your first product listing by adding a new product or editing an existing one in WooCommerce. You can list a product on each of the marketplaces you\'ve added. Once listed, the product info and inventory will sync automatically. Visit %1$sProducts%2$s to get started.', 'mwc-core'),
            '<a href="'.esc_url($productsPageUrl).'">',
            '</a>'
        );

        $notice = (new Notice())
            ->setId('mwc_marketplaces_new_feature')
            ->setType(Notice::TYPE_INFO)
            ->setRestrictedUserCapabilities(['manage_woocommerce'])
            ->setDismissible(true)
            ->setTitle(esc_html__('Welcome to GoDaddy Marketplaces!', 'mwc-core'))
            ->setContent($content)
            ->setRenderCondition(function () {
                return MarketplacesPage::isMarketplacesPage();
            });

        Notices::enqueueAdminNotice($notice);
    }

    /**
     * Displays a dismissible admin notice notifying that the currency or country settings are not supported.
     *
     * @return void
     */
    protected static function maybeEnqueueCurrencyOrCountryAdminNotice() : void
    {
        if (static::isSupportedCurrency() && static::isSupportedCountry()) {
            return;
        }

        $notice = (new Notice())
            ->setId('mwc_marketplaces_unsupported_currency_or_country_settings')
            ->setType(Notice::TYPE_WARNING)
            ->setRestrictedUserCapabilities(['manage_woocommerce'])
            ->setDismissible(true)
            ->setContent(__('Your store address must be in the US and currency US dollars in order to sync products, inventory, and orders with Marketplaces & Social sales channels.', 'mwc-core'));

        Notices::enqueueAdminNotice($notice);
    }

    /**
     * Displays a dismissible admin notice notifying that the weight or dimension unit settings are not supported.
     *
     * @return void
     */
    protected static function maybeEnqueueUnitsAdminNotice() : void
    {
        if (static::isSupportedWeightUnit() && static::isSupportedDimensionUnit()) {
            return;
        }

        $content = __('Your store weight and dimensions units must be in "lbs" and "in" in order to sync products, inventory, and orders with Marketplaces & Social sales channels.', 'mwc-core');
        $buttonText = __('Update settings', 'mwc-core');
        $buttonUrl = SiteRepository::getAdminUrl('admin.php?page=wc-settings&tab=products');

        $noticeMarketplacesPage = (new Notice())
            ->setId('mwc_marketplaces_unsupported_unit_settings')
            ->setType(Notice::TYPE_WARNING)
            ->setRestrictedUserCapabilities(['manage_woocommerce'])
            ->setDismissible(false)
            ->setContent($content)
            ->setButtonText($buttonText)
            ->setButtonUrl($buttonUrl)
            ->setRenderCondition(function () {
                return static::isMarketplacesPage();
            });

        $notice = (new Notice())
            ->setId('mwc_marketplaces_unsupported_unit_settings_dismissible')
            ->setType(Notice::TYPE_WARNING)
            ->setRestrictedUserCapabilities(['manage_woocommerce'])
            ->setDismissible(true)
            ->setContent($content)
            ->setButtonText($buttonText)
            ->setButtonUrl($buttonUrl)
            ->setRenderCondition(function () {
                return ! static::isMarketplacesPage();
            });

        Notices::enqueueAdminNotice($noticeMarketplacesPage);
        Notices::enqueueAdminNotice($notice);
    }

    /**
     * Displays a dismissible admin notice notifying that the inventory settings are not supported.
     *
     * @return void
     */
    protected static function maybeEnqueueInventoryAdminNotice() : void
    {
        if (static::isSupportedStockManagement()) {
            return;
        }

        $content = __('Stock management must be enabled in order to sync products, inventory, and orders with Marketplaces & Social sales channels.', 'mwc-core');
        $buttonText = __('Update settings', 'mwc-core');
        $buttonUrl = SiteRepository::getAdminUrl('admin.php?page=wc-settings&tab=products&section=inventory');

        $noticeMarketplacesPage = (new Notice())
            ->setId('mwc_marketplaces_unsupported_inventory_settings')
            ->setType(Notice::TYPE_WARNING)
            ->setRestrictedUserCapabilities(['manage_woocommerce'])
            ->setDismissible(false)
            ->setContent($content)
            ->setButtonText($buttonText)
            ->setButtonUrl($buttonUrl)
            ->setRenderCondition(function () {
                return static::isMarketplacesPage();
            });

        $notice = (new Notice())
            ->setId('mwc_marketplaces_unsupported_inventory_settings_dismissible')
            ->setType(Notice::TYPE_WARNING)
            ->setRestrictedUserCapabilities(['manage_woocommerce'])
            ->setDismissible(true)
            ->setContent($content)
            ->setButtonText($buttonText)
            ->setButtonUrl($buttonUrl)
            ->setRenderCondition(function () {
                return ! static::isMarketplacesPage();
            });

        Notices::enqueueAdminNotice($noticeMarketplacesPage);
        Notices::enqueueAdminNotice($notice);
    }

    /**
     * Displays a non-dismissible admin notice in the Marketplaces page if the site is a staging site.
     *
     * @return void
     * @throws PlatformRepositoryException
     */
    protected static function maybeEnqueueStagingSiteAdminNotice() : void
    {
        if (! PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->isStagingSite()) {
            return;
        }

        Notices::enqueueAdminNotice((new Notice())
            ->setId('mwc_marketplaces_syncing_disabled_on_staging_site')
            ->setType(Notice::TYPE_WARNING)
            ->setRestrictedUserCapabilities(['manage_woocommerce'])
            ->setDismissible(false)
            ->setContent(__('Product, inventory, and order syncing is disabled in Staging to prevent conflicts with your Production site.', 'mwc-core'))
            ->setRenderCondition(function () {
                return MarketplacesPage::isMarketplacesPage();
            })
        );
    }

    /**
     * Displays a non-dismissible admin notice if the order limit is approaching.
     *
     * @return void
     */
    protected static function enqueueOrderLimitApproachingAdminNotice() : void
    {
        $content = sprintf(
            /* translators: Placeholders: %1$s - opening HTML link tag <a> to products page, %2$s - closing HTML link tag </a> */
            __('You\'ve used 90&#37; of your included monthly Marketplaces and Social orders. Upgrade soon to process more orders. %1$sUpgrade Plan%2$s', 'mwc-core'),
            '<a href="https://mwcstores.godaddy.com/my-subscription" target="_blank">',
            '</a>'
        );

        Notices::enqueueAdminNotice((new Notice())
            ->setId('mwc_marketplaces_order_limit_approaching')
            ->setType(Notice::TYPE_WARNING)
            ->setRestrictedUserCapabilities(['manage_woocommerce'])
            ->setDismissible(false)
            ->setContent($content)
            ->setRenderCondition(function () {
                return OrderRepository::isNearMonthlyMarketplacesOrdersLimit();
            })
        );
    }

    /**
     * Displays a non-dismissible admin notice if the order limit was reached.
     *
     * @return void
     */
    protected static function enqueueOrderLimitReachedAdminNotice() : void
    {
        $content = sprintf(
            /* translators: Placeholders: %1$s - opening HTML link tag <a> to products page, %2$s - closing HTML link tag </a> */
            __('You\'ve used all your included monthly Marketplaces and Social orders. Upgrade now to process more orders. %1$sUpgrade Plan%2$s', 'mwc-core'),
            '<a href="https://mwcstores.godaddy.com/my-subscription" target="_blank">',
            '</a>'
        );

        Notices::enqueueAdminNotice((new Notice())
            ->setId('mwc_marketplaces_order_limit_reached')
            ->setType(Notice::TYPE_WARNING)
            ->setRestrictedUserCapabilities(['manage_woocommerce'])
            ->setDismissible(false)
            ->setContent($content)
            ->setRenderCondition(function () {
                return OrderRepository::hasReachedMonthlyMarketplacesOrdersLimit();
            })
        );
    }

    public static function isMarketplacesPage() : bool
    {
        return 'gd-marketplaces' === ArrayHelper::get($_GET, 'page');
    }

    /**
     * Returns the store's ID.
     *
     * This is a temporary method until more commerce features (particularly the store switcher modal) are implemented.
     * The difference between this and the {@see AbstractStoreRepository::getStoreId()} method is that we always fall back to using the poynt store ID.
     *
     * @TODO Remove this method when the store switcher feature is available & use {@see AbstractStoreRepository::getStoreId()} instead {agibson 2022-12-19}
     *
     * @return string
     */
    public static function getStoreId() : string
    {
        try {
            if ($storeId = PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getStoreRepository()->getStoreId()) {
                return TypeHelper::string($storeId, '');
            }
        } catch (PlatformRepositoryException $e) {
            // do nothing
        }

        return TypeHelper::string(Configuration::get('payments.poynt.siteStoreId', ''), '');
    }
}
