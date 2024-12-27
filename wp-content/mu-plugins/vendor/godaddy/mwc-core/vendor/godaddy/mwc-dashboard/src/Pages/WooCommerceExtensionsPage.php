<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Pages;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Content\AbstractAdminPage;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\SanitizationHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Redirect;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedExtensionsRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Common\Traits\Features\IsConditionalFeatureTrait;
use WC_Admin_Addons;
use WC_Helper;
use WC_Helper_Updater;

/**
 * The WooCommerce Extensions page.
 */
class WooCommerceExtensionsPage extends AbstractAdminPage
{
    use IsConditionalFeatureTrait;

    /** @var string the slug of the Available Extensions tab */
    const TAB_AVAILABLE_EXTENSIONS = 'available_extensions';

    /** @var string the slug of the Browse Extensions tab */
    const TAB_BROWSE_EXTENSIONS = 'browse_extensions';

    /** @var string the slug of the Subscriptions tab */
    const TAB_SUBSCRIPTIONS = 'subscriptions';

    /** @var string ID of the div element inside which the page will be rendered */
    protected $divId;

    /** @var string String of styles to apply to the div element */
    protected $divStyles;

    /**
     * Constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->screenId = 'wc-addons';
        $this->title = __('WooCommerce extensions', 'mwc-dashboard');
        $this->menuTitle = _x('Extensions', 'WooCommerce extensions', 'mwc-dashboard');
        $this->parentMenuSlug = 'woocommerce';

        $this->capability = 'manage_woocommerce';

        $this->divId = 'mwc-extensions';
        $this->divStyles = '';

        parent::__construct();

        $this->addHooks();
    }

    /**
     * @param string $tab
     * @return void
     * @throws Exception
     */
    public function redirectToExtensions(string $tab) : void
    {
        // if invalid tab, don't redirect.
        if (! in_array($tab, [self::TAB_SUBSCRIPTIONS, self::TAB_AVAILABLE_EXTENSIONS, self::TAB_BROWSE_EXTENSIONS], true)) {
            return;
        }

        $this->getRedirect()->setLocation(admin_url('admin.php'))
            ->setQueryParameters([
                'page' => 'wc-addons',
                'tab'  => $tab,
            ])
            ->execute();
    }

    /**
     * Renders the page HTML.
     */
    public function renderDivContainer()
    {
        ?>
        <div id="<?php echo $this->divId; ?>" style="<?php echo $this->divStyles; ?>"></div>
        <?php
    }

    /**
     * Adds the menu page.
     *
     * @internal
     *
     * @see https://developer.wordpress.org/reference/functions/add_submenu_page/
     *
     * @return self
     * @throws Exception
     */
    public function addMenuItem() : AbstractAdminPage
    {
        if ($count = $this->getUpdatesCountHtml()) {
            /* translators: Placeholder: %s - WooCommerce extensions count HTML */
            $this->menuTitle = sprintf(esc_html__('Extensions %s', 'mwc-dashboard'), $count);
        }

        return parent::addMenuItem();
    }

    /**
     * Registers the page hooks.
     *
     * @throws Exception
     */
    public function addHooks()
    {
        Register::filter()
            ->setGroup('woocommerce_show_addons_page')
            ->setHandler('__return_false')
            ->setPriority(PHP_INT_MAX)
            ->setArgumentsCount(1)
            ->execute();

        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'maybeRedirectToAvailableExtensionsTab'])
            ->execute();

        Register::action()
            ->setGroup('admin_menu')
            ->setHandler([$this, 'removeRedundantExtensionsMenuItems'])
            ->setPriority(PHP_INT_MAX)
            ->execute();

        $this->maybeAddRenderContentHook();
    }

    /**
     * Removes some redundant Extensions menu items that appear in WC 8.2+.
     *
     * @internal
     *
     * @return void
     */
    public function removeRedundantExtensionsMenuItems() : void
    {
        if (version_compare(WooCommerceRepository::getWooCommerceVersion() ?: '', '8.2', '<')) {
            return;
        }

        // The new WC react-based extensions page needs to be removed from the menu.
        remove_submenu_page('woocommerce', 'wc-admin&path=/extensions');

        // In WC 8.3+ do not remove the 'wc-addons' menu.
        if (version_compare(WooCommerceRepository::getWooCommerceVersion() ?: '', '8.3', '>=')) {
            return;
        }

        remove_submenu_page('woocommerce', 'wc-addons');
    }

    /**
     * Registers the menu page.
     *
     * Overridden to change the priority of the handler to 20.
     *
     * @return self
     * @throws Exception
     */
    protected function registerMenuItem() : AbstractAdminPage
    {
        try {
            if ($this->shouldAddMenuItem()) {
                Register::action()
                    ->setGroup('admin_menu')
                    ->setHandler([$this, 'addMenuItem'])
                    ->setPriority(100)
                    ->execute();
            }
        } catch (Exception $ex) {
            // TODO: log an error using a wrapper for WC_Logger {WV 2021-02-15}
            // throw new Exception('Cannot register the menu item: '.$ex->getMessage());
        }

        return $this;
    }

    /**
     * Checks if assets should be enqueued or not.
     *
     * @return bool
     */
    protected function shouldEnqueueAssets() : bool
    {
        if ($screen = $this->getCurrentScreen()) {
            return 'woocommerce_page_'.$this->screenId === $screen->id;
        }

        return false;
    }

    /**
     * Gets the current admin screen.
     *
     * TODO: move to WordPressRepository
     *
     * @return \WP_Screen|null
     */
    protected function getCurrentScreen()
    {
        return get_current_screen();
    }

    /**
     * Enqueues/loads registered assets.
     *
     * @throws Exception
     */
    protected function enqueuePageAssets()
    {
        Enqueue::style()
            ->setHandle("{$this->divId}-style")
            ->setSource(Configuration::get('mwc_extensions.assets.css.admin.url'))
            ->execute();
    }

    /**
     * Redirects the default page to the Available Extensions tab.
     *
     * @internal
     *
     * @throws Exception
     */
    public function maybeRedirectToAvailableExtensionsTab() : void
    {
        $page = SanitizationHelper::input(TypeHelper::string(ArrayHelper::get($_GET, 'page'), ''));
        $tab = SanitizationHelper::input(TypeHelper::string(ArrayHelper::get($_GET, 'tab'), ''));

        /* @NOTE we need to be past the `admin_init` hook to use {@see WordPressRepository::isCurrentScreen()} here {unfulvio 2022-02-10} */
        if (WordPressRepository::isAdmin()) {
            if ($this->isWcAddonsPage($page, $tab)) {
                $this->redirectToExtensions(self::TAB_AVAILABLE_EXTENSIONS);
            } elseif ($this->isWcReactExtensionsPage($page)) {
                // redirect the new WC "browse extensions" tab to the GD extensions browse tab
                if ($tab == 'extensions') {
                    $this->redirectToExtensions(self::TAB_BROWSE_EXTENSIONS);
                }

                // else redirect WC extensions page to GD available extensions tab.
                $this->redirectToExtensions(self::TAB_AVAILABLE_EXTENSIONS);
            }
        }
    }

    /**
     * Is the wc-addons page.
     *
     * @param string $page
     * @param string $tab
     * @return bool
     */
    protected function isWcAddonsPage(string $page, string $tab) : bool
    {
        $section = SanitizationHelper::input(TypeHelper::string(ArrayHelper::get($_GET, 'section'), ''));
        $helperConnect = TypeHelper::bool(ArrayHelper::get($_GET, 'wc-helper-connect', false), false);

        return 'wc-addons' === $page && ! $helperConnect && ! $section && ! $tab;
    }

    /**
     * Is the new react-based WC extensions page.
     *
     * @param string $page
     * @return bool
     */
    protected function isWcReactExtensionsPage(string $page) : bool
    {
        $path = SanitizationHelper::input(TypeHelper::string(ArrayHelper::get($_GET, 'path'), ''));

        return  'wc-admin' === $page && '/extensions' === $path;
    }

    /**
     * Returns a new redirect object.
     *
     * @return Redirect
     */
    protected function getRedirect() : Redirect
    {
        return new Redirect();
    }

    /**
     * Renders the page HTML.
     *
     * @return void
     * @throws PlatformRepositoryException
     */
    public function render() : void
    {
        // when running WC 8.2+, the content will be rendered at the top of the `woocommerce_page_wc-addons` action hook instead,
        // to prevent WooCommerce react components to load ahead of ours
        if (version_compare(WooCommerceRepository::getWooCommerceVersion() ?: '', '8.2', '<')) {
            $this->renderContent();
        }
    }

    /**
     * Renders the content for the addons page.
     *
     * @return void
     * @throws PlatformRepositoryException
     */
    public function renderContent() : void
    {
        // @NOTE: Clearing at beginning and end is required as the count is loaded and cache set multiple times during page render {JO 2021-02-15}
        $this->maybeClearUpdatesCacheCount();

        $current_tab = $this->getCurrentTab();

        // the min-height here helps to mask the WooCommerce component that is rendered in the background until it is removed
        ?>
        <div class="wrap woocommerce wc_addons_wrap mwc-dashboard-wc-addons-wrap" style="min-height:100vh">

            <nav class="nav-tab-wrapper woo-nav-tab-wrapper mwc-dashboard-nav-tab-wrapper">
                <?php
                foreach ($this->getTabs() as $slug => $tab) {
                    printf(
                        '<a href="%1$s" class="nav-tab%2$s">%3$s</a>',
                        esc_url($tab['url']),
                        ($current_tab === $slug) ? ' nav-tab-active' : '',
                        $tab['label']
                    );
                } ?>
            </nav>

            <h1 class="screen-reader-text"><?php esc_html_e('WooCommerce Extensions', 'woocommerce'); ?></h1>

            <?php $this->renderTab($current_tab); ?>

        </div>

        <div class="clear"></div>

        <?php

        // this is used to remove from the DOM a component added by WC 8.2+ that will output its own extensions
        if ($current_tab === static::TAB_AVAILABLE_EXTENSIONS && version_compare(WooCommerceRepository::getWooCommerceVersion() ?: '', '8.2', '>=')) {
            wc_enqueue_js("jQuery( '.wc-addons-wrap' ).remove();");
        }

        // @NOTE: Clearing at beginning and end is required as the count is loaded and cache set multiple times during page render {JO 2021-02-15}
        $this->maybeClearUpdatesCacheCount();
    }

    /**
     * Deletes the updates count cache if the current tab is the Subscriptions tab.
     */
    private function maybeClearUpdatesCacheCount()
    {
        if ($this->getCurrentTab() === self::TAB_SUBSCRIPTIONS) {
            delete_transient('_woocommerce_helper_updates_count');
        }
    }

    /**
     * Gets the slug for the currently active tab.
     *
     * @return string
     */
    private function getCurrentTab() : string
    {
        if (! $tab = SanitizationHelper::input(ArrayHelper::get($_GET, 'tab', ''))) {
            $tab = static::TAB_AVAILABLE_EXTENSIONS;
        }

        if ($section = ArrayHelper::get($_GET, 'section')) {
            // self::TAB_SUBSCRIPTIONS necessary to support redirect requests after a merchant connects the site to WooCommerce.com and filter views in the Subscriptions tab
            // self::TAB_BROWSE_EXTENSIONS necessary to support the extensions search and extension cateogires features in the Browse Extensions tab
            $tab = $section === 'helper' ? self::TAB_SUBSCRIPTIONS : self::TAB_BROWSE_EXTENSIONS;
        }

        return $tab;
    }

    /**
     * Gets a list of tabs to render indexed by the tab slug.
     *
     * @return array[]
     * @throws PlatformRepositoryException
     */
    protected function getTabs() : array
    {
        $url = admin_url('admin.php?page=wc-addons');

        $tabs = [
            self::TAB_AVAILABLE_EXTENSIONS => [
                'label' => PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->isReseller()
                    ? esc_html_x('Included Extensions', 'WooCommerce extensions', 'mwc-dashboard')
                    : esc_html_x('GoDaddy Included Extensions', 'WooCommerce extensions', 'mwc-dashboard'),
                'url' => $url.'&'.ArrayHelper::query(['tab' => self::TAB_AVAILABLE_EXTENSIONS]),
            ],
            self::TAB_BROWSE_EXTENSIONS => [
                'label' => esc_html_x('Browse Extensions', 'WooCommerce extensions', 'woocommerce'),
                'url'   => $url.'&'.ArrayHelper::query(['tab' => self::TAB_BROWSE_EXTENSIONS]),
            ],
            self::TAB_SUBSCRIPTIONS => [
                'label' => esc_html__('WooCommerce.com Subscriptions', 'woocommerce').$this->getUpdatesCountHtml(),
                'url'   => $url.'&'.ArrayHelper::query(['tab' => self::TAB_SUBSCRIPTIONS, 'section' => 'helper']),
            ],
        ];

        if (PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getGoDaddyCustomer()->getFederationPartnerId() === 'WORLDPAY'
            // TODO Restore the ability to browse extensions when a fix is determined for Woo 9.0+. {ajaynes 2024-07-22}
            || version_compare(WooCommerceRepository::getWooCommerceVersion() ?: '', '9.0', '>=')
        ) {
            unset($tabs[self::TAB_BROWSE_EXTENSIONS]);
        }

        return $tabs;
    }

    /**
     * Gets the HTML for the number of products that have updates, with managed plugins removed from the count.
     *
     * @return string
     */
    protected function getUpdatesCountHtml() : string
    {
        $filter = Register::filter()
            ->setGroup('transient__woocommerce_helper_updates')
            ->setHandler([$this, 'removeManagedPluginsFromCount'])
            ->setPriority(10)
            ->setArgumentsCount(1);

        try {
            $filter->execute();

            $html = WC_Helper_Updater::get_updates_count_html();

            $filter->deregister();
        } catch (Exception $exception) {
            $html = '';
        }

        return $html;
    }

    /**
     * Removes managed plugins from the list of plugins that have updates.
     *
     * @internal
     *
     * @param mixed $transient_value array of cached WooCommerce plugins data
     * @return mixed
     * @throws Exception
     */
    public function removeManagedPluginsFromCount($transient_value)
    {
        // bail if not an array
        if (! ArrayHelper::accessible($transient_value)) {
            return $transient_value;
        }

        $urls = array_map(static function ($plugin) {
            return $plugin->getHomepageUrl();
        }, ManagedExtensionsRepository::getManagedPlugins());

        $transient_value['products'] = ArrayHelper::where(ArrayHelper::get($transient_value, 'products', []), static function ($value) use ($urls) {
            return ! in_array(ArrayHelper::get($value, 'url'), $urls, true);
        });

        return $transient_value;
    }

    /**
     * Renders the content for the given tab.
     *
     * @param string $currentTab
     */
    protected function renderTab(string $currentTab)
    {
        $methodName = 'render'.str_replace(' ', '', ucwords(str_replace('_', ' ', $currentTab))).'Tab';

        if (method_exists($this, $methodName)) {
            $this->{$methodName}();
        }
    }

    /**
     * Renders the content for the GoDaddy Included Extensions tab.
     */
    protected function renderAvailableExtensionsTab()
    {
        $this->renderDivContainer();
    }

    /**
     * Renders the content for the Browse Extensions tab.
     */
    protected function renderBrowseExtensionsTab()
    {
        /*
         * Though WC_Admin_Addons::output() was removed in WooCommerce 9.0,
         * retain the ability to browse extensions in older versions.
         *
         * TODO Browsing ability will need to be restored later {ajaynes 2024-07-22}
         */
        if (version_compare(WooCommerceRepository::getWooCommerceVersion() ?: '', '9.0', '<')
            && method_exists('WC_Admin_Addons', 'output')
        ) {
            WC_Admin_Addons::output();
        }
    }

    /**
     * Renders the content for the Subscriptions tab.
     */
    protected function renderSubscriptionsTab()
    {
        WC_Helper::render_helper_output();
    }

    /**
     * Determines whether the feature can be loaded.
     *
     * @return bool
     * @throws Exception
     */
    public static function shouldLoadConditionalFeature() : bool
    {
        return WooCommerceRepository::isWooCommerceActive()
            && PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->hasEcommercePlan();
    }

    /**
     * For WC 8.2 and above we need to register a hook to render the page content as early as possible.
     *
     * @return void
     * @throws Exception
     */
    public function maybeAddRenderContentHook() : void
    {
        if (version_compare(WooCommerceRepository::getWooCommerceVersion() ?: '', '8.2', '>=')) {
            Register::action()
                ->setGroup('woocommerce_page_wc-addons')
                ->setHandler([$this, 'renderContent'])
                ->setPriority(PHP_INT_MIN)
                ->execute();
        }
    }
}
