<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Pages;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Content\AbstractAdminPage;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Marketplaces;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Channel;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Repositories\ChannelRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Views\Components\GoDaddyBranding;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Views\Components\GoDaddyIcon;

/**
 * The Marketplaces admin page.
 */
class MarketplacesPage extends AbstractAdminPage implements ComponentContract
{
    /** @var string the page and menu item slug */
    const SLUG = 'gd-marketplaces';

    /** @var string required capability to interact with page and related menu item */
    const CAPABILITY = 'manage_woocommerce';

    /** @var int the menu index level to be located right below the Products menu */
    const MENU_LEVEL = 56;

    /**
     * Sets up the page properties.
     */
    public function __construct()
    {
        $this->screenId = static::SLUG;
        $this->title = __('Marketplaces', 'mwc-core');
        $this->menuTitle = __('Marketplaces', 'mwc-core');
        $this->parentMenuSlug = static::SLUG;
        $this->capability = static::CAPABILITY;

        parent::__construct();
    }

    /**
     * Loads the page components.
     *
     * @return void
     * @throws Exception
     */
    public function load()
    {
        Register::action()
            ->setGroup('wp_after_admin_bar_render')
            ->setHandler([$this, 'renderHeading'])
            ->setPriority(PHP_INT_MIN)
            ->execute();

        Register::action()
            ->setGroup('admin_enqueue_scripts')
            ->setHandler([$this, 'maybeEnqueueAssets'])
            ->execute();

        Register::filter()
            ->setGroup('admin_footer_text')
            ->setHandler([$this, 'renderBranding'])
            ->setPriority(PHP_INT_MAX)
            ->execute();
    }

    /**
     * Overrides the default addMenuItem method to be able to add a root menu item.
     */
    public function addMenuItem() : AbstractAdminPage
    {
        $pageTitle = __('Marketplaces', 'mwc-core');
        $pageIcon = $this->getMarketplacesMenuIcon();

        add_menu_page(
            $pageTitle,
            $pageTitle.'<div id="gd-marketplaces-main-menu-item"></div>',
            static::CAPABILITY,
            static::SLUG,
            [$this, 'render'],
            $pageIcon,
            static::MENU_LEVEL
        );

        return $this;
    }

    /**
     * Loads the Marketplaces menu item from its SVG file.
     *
     * @return string
     */
    protected function getMarketplacesMenuIcon() : string
    {
        $iconFilePath = StringHelper::trailingSlash(Configuration::get('mwc.directory', '')).'assets/images/marketplaces/gd-marketplaces-icon.svg';

        try {
            return 'data:image/svg+xml;base64,'.$this->getBase64FileContents($iconFilePath);
        } catch (Exception $exception) {
            new SentryException('Marketplaces icon not loaded', $exception);
        }

        return '';
    }

    /**
     * Gets the SVG file base 64 contents.
     *
     * @param string $iconFilePath
     *
     * @return string
     */
    protected function getBase64FileContents(string $iconFilePath) : string
    {
        return base64_encode(file_get_contents($iconFilePath) ?: '');
    }

    /**
     * Determines if the current admin page is the Marketplaces page.
     *
     * @return bool
     */
    public static function isMarketplacesPage() : bool
    {
        $screen = WordPressRepository::getCurrentScreen();

        if (! $screen) {
            return false;
        }

        return 'admin-'.static::SLUG === $screen->getPageId() || static::SLUG.'-'.static::SLUG === $screen->getPageId();
    }

    /**
     * Determines whether the Marketplaces assets should be loaded for the current screen.
     *
     * @return bool
     */
    protected function shouldEnqueueAssets() : bool
    {
        return static::isMarketplacesPage();
    }

    /**
     * Enqueues the page assets.
     *
     * @return void
     * @throws Exception
     */
    protected function enqueueAssets() : void
    {
        Enqueue::style()
            ->setHandle('gd-marketplaces')
            ->setSource(WordPressRepository::getAssetsUrl('css/features/marketplaces/admin/marketplaces-page.css'))
            ->execute();
    }

    /**
     * Gets the sales channel button URL.
     *
     * @return string
     * @throws Exception
     */
    public static function getSalesChannelButtonUrl() : string
    {
        if ($storeId = Marketplaces::getStoreId()) {
            return Marketplaces::getCommerceHubUrl("/home/sales-channels?storeId={$storeId}&ua_placement=shared_header");
        } else {
            // Legacy URL for stores without an ID.
            $ventureId = PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getVentureId();

            return Marketplaces::getSalesChannelsUrl("/{$ventureId}/marketplaces");
        }
    }

    /**
     * Renders the page contents.
     *
     * @internal
     *
     * @return void
     */
    public function render() : void
    {
        ?>
        <div id="gd-marketplaces" class="gd-marketplaces-page">
            <h2><?php esc_html_e('Manage Sales Channels', 'mwc-core'); ?></h2>
        <?php $connectedChannels = ChannelRepository::getConnected(false);

        try {
            if (! empty($connectedChannels)) {
                $this->renderManageConnectedSalesChannels($connectedChannels);
            } else {
                $this->renderNoConnectedSalesChannels();
            }
        } catch (Exception $exception) {
            // since we are in a hook callback context we catch the exception and forward to Sentry instead of throwing
            new SentryException($exception->getMessage(), $exception);
        }

        $this->renderResources(); ?>
        </div>
        <?php
    }

    /**
     * Renders the page heading.
     *
     * @internal
     *
     * @return void
     */
    public function renderHeading() : void
    {
        if (! static::isMarketplacesPage()) {
            return;
        } ?>
        <div id="gd-marketplaces-page-heading">
            <h1><?php GoDaddyIcon::getNewInstance()->render();
        esc_html_e('Marketplaces &amp; Social', 'mwc-core'); ?></h1>
        </div>
        <?php
    }

    /**
     * Renders the output to manage connected sales channels.
     *
     * @param Channel[] $connectedChannels
     *
     * @return void
     * @throws Exception
     */
    protected function renderManageConnectedSalesChannels(array $connectedChannels) : void
    {
        $storeId = Marketplaces::getStoreId();
        $ventureId = PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getVentureId();

        foreach ($connectedChannels as $channel) {?>
            <p><a href="<?php echo esc_url(Marketplaces::getSalesChannelsUrl('/marketplaces/channels/'.$channel->getId()."/listings?storeId={$storeId}&ventureId={$ventureId}")); ?>" target="_blank"><span style="margin-right: 8px;"><?php echo ChannelRepository::getIcon($channel->getType()); ?></span><?php echo ChannelRepository::getLabel($channel->getType() ?: '');
            $this->renderNewWindowIcon(); ?></a></p>
        <?php } ?>
        <?php $this->renderAddSalesChannelButton(); ?>
        <p class="manage-listing-templates"><a href="<?php echo esc_url(Marketplaces::getMarketplacesUrl('/settings/templates')); ?>" target="_blank"><?php esc_html_e('Manage Listing Templates', 'mwc-core');
        $this->renderNewWindowIcon(); ?></a></p>
        <?php
    }

    /**
     * Renders the output when there are no connected sales channels.
     *
     * @return void
     * @throws Exception
     */
    protected function renderNoConnectedSalesChannels() : void
    {
        ?>
        <p><?php esc_html_e('You haven\'t added any sales channels yet. Add sales channels in order to post your listings on third party marketplaces and social platforms.', 'mwc-core'); ?></p>
        <?php
        $this->renderAddSalesChannelButton();
    }

    /**
     * Renders the button to add a sales channel.
     *
     * @return void
     * @throws Exception
     */
    protected function renderAddSalesChannelButton() : void
    {
        $addSalesChannelAttributes = 'href="'.esc_url(static::getSalesChannelButtonUrl()).'" target="_blank"';
        $refreshPageBtn = ' <button onclick="window.location.reload();" class="button button-link updating-message refresh-connected-channels">'.esc_html_x('Refresh', 'Refreshes the page', 'mwc-core').'</button>';

        if (! Marketplaces::areSettingsSupported()) {
            $addSalesChannelAttributes = 'disabled="disabled"';
            $refreshPageBtn = '';
        }

        $addSalesChannelLink = '<a '.$addSalesChannelAttributes.' class="button button-primary">'.esc_html__('Add Sales Channel', 'mwc-core').'</a>';

        echo '<p class="manage-sales-channels-buttons">'.$addSalesChannelLink.$refreshPageBtn.'</p>';
    }

    /**
     * Renders the Marketplaces resources information.
     *
     * @return void
     */
    protected function renderResources() : void
    {
        ?>
        <h2><?php esc_html_e('Resources', 'mwc-core'); ?></h2>
        <p><a href="https://godaddy.com/help/a-41221" target="_blank"><?php esc_html_e('What is Marketplaces &amp; Social?', 'mwc-core');
        $this->renderNewWindowIcon(); ?></a></p>
        <p><a href="https://godaddy.com/help/a-41222" target="_blank"><?php esc_html_e('How to connect sales channels', 'mwc-core');
        $this->renderNewWindowIcon(); ?></a></p>
        <p><a href="https://godaddy.com/help/a-41223" target="_blank"><?php esc_html_e('What products are eligible for Marketplaces & Social sales channels?', 'mwc-core');
        $this->renderNewWindowIcon(); ?></a></p>
        <?php
    }

    /**
     * Renders the GoDaddy branding at the bottom of the Marketplaces page.
     *
     * @param string|mixed $defaultText
     * @return string|mixed
     */
    public function renderBranding($defaultText)
    {
        if (! static::isMarketplacesPage()) {
            return $defaultText;
        }

        ob_start();

        GoDaddyBranding::getInstance()
            // these CSS hacks are needed to make sure the branding will be kept visible by WordPress in mobile viewports
            ->addStyle('<style>#wpfooter { display: block ! important; }</style>')
            ->addStyle('<style>@media only screen and (max-width: 960px) { .auto-fold #wpcontent, .auto-fold #wpfooter { margin-left: 0; } }</style>')
            ->render();

        return ob_get_clean();
    }

    /**
     * Renders the `<span>` tag for the new window icon.
     *
     * @return void
     */
    protected function renderNewWindowIcon() : void
    {
        try {
            $imageUrl = WordPressRepository::getAssetsUrl('images/new-window-icon.svg');
        } catch (Exception $exception) {
            new SentryException('New window icon not loaded', $exception);

            return;
        }

        if (empty($imageUrl)) {
            return;
        } ?><span style="margin-left: 8px;"><img src="<?php echo esc_url($imageUrl); ?>" width="12" height="12"/></span>
        <?php
    }
}
