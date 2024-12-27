<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Menu;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Content\AbstractAdminPage;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Traits\Features\IsConditionalFeatureTrait;
use GoDaddy\WordPress\MWC\Dashboard\Pages\GetHelpPage;

/**
 * Class GetHelpMenu handler.
 *
 * @since 1.0.0
 */
class GetHelpMenu
{
    use IsConditionalFeatureTrait;

    /**
     * The minimum capability to load the menu items.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const CAPABILITY = 'administrator';

    /**
     * The slug for the top-level menu item.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const MENU_SLUG = 'godaddy-get-help';

    /**
     * The page to associate with the menu item.
     *
     * @since 1.0.0
     *
     * @var AbstractAdminPage
     */
    protected $page;

    /**
     * The app handle prefix for enqueuing assets.
     *
     * @var string
     */
    protected $appHandle;

    /**
     * GetHelpMenu constructor.
     *
     * @since 1.0.0
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->addAdminActions();

        $this->page = (new GetHelpPage());
        $this->appHandle = 'mwcDashboardClient';
    }

    /**
     * Add WordPress actions to add menu item as well enqueue it's assets.
     *
     * @since 1.0.0
     *
     * @throws Exception
     */
    protected function addAdminActions()
    {
        // add main menu item
        Register::action()
            ->setGroup('admin_menu')
            ->setHandler([$this, 'addMenuItem'])
            ->execute();

        // enqueue the style assets
        Register::action()
            ->setGroup('admin_enqueue_scripts')
            ->setHandler([$this, 'enqueueAdminStyles'])
            ->execute();
    }

    /**
     * Enqueues menu style assets.
     *
     * @since 1.0.0
     *
     * @throws Exception
     */
    public function enqueueAdminStyles()
    {
        Enqueue::style()
            ->setHandle("{$this->appHandle}-admin")
            ->setSource(Configuration::get('mwc_dashboard.assets.css.admin.url'))
            ->execute();
    }

    /**
     * Adds parent menu item.
     *
     * @internal
     */
    public function addMenuItem()
    {
        $pageTitle = _x('Get Help', 'Page title for requesting GoDaddy support assistance via form', 'mwc-dashboard');
        $pageIcon = 'dashicons-sos';

        // since we are in a hook callback context, we should catch any exceptions
        try {
            $iconFilePath = Configuration::get('mwc_dashboard.assets.images.go_icon.url');
        } catch (Exception $exception) {
            // ignore the icon and use the default SOS icon
            $iconFilePath = null;
        }

        // since we are in a hook callback context, we should catch any exceptions
        try {
            $isReseller = PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->isReseller();
        } catch (PlatformRepositoryException $exception) {
            $isReseller = false;
        }

        if ($iconFilePath && is_readable($iconFilePath) && ! $isReseller) {
            $pageIcon = 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($iconFilePath) ?: '');
        }

        add_menu_page(
            $pageTitle,
            $pageTitle.'<div id="mwc-dashboard-main-menu-item"></div>',
            self::CAPABILITY,
            self::MENU_SLUG,
            '__return_empty_string', // TODO: update later to point to the actual page render method. NM {2020-12-29}
            $pageIcon,
            1
        );
    }

    /**
     * Determines whether the feature should be loaded.
     *
     * @return bool
     * @throws Exception
     */
    public static function shouldLoadConditionalFeature() : bool
    {
        // should not display if Dashboard is disabled through configuration
        if (! Configuration::get('features.mwc_dashboard')) {
            return false;
        }

        // should not display if WooCommerce is not active
        if (! WooCommerceRepository::isWooCommerceActive()) {
            return false;
        }

        $platform = PlatformRepositoryFactory::getNewInstance()->getPlatformRepository();

        // should only display for pro, ultimate* or mwc ecommerce plans
        $planName = $platform->getPlan()->getName();

        if (
            $platform->hasEcommercePlan()
            || StringHelper::startsWith($planName, 'pro')
            || StringHelper::startsWith($planName, 'ultimate')
        ) {
            // display the dashboard for end-customers (non-resellers) or resellers with a support agreement only
            return ! $platform->isReseller() || ManagedWooCommerceRepository::isResellerWithSupportAgreement();
        }

        return false;
    }
}
