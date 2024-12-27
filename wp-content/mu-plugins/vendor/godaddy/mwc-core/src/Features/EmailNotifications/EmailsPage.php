<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Content\AbstractAdminPage;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\HostingPlans\Repositories\HostingPlanRepository;

/**
 * Email notifications page.
 */
class EmailsPage extends AbstractAdminPage implements ComponentContract
{
    /** @var string the page and menu item slug */
    public const SLUG = 'gd-email-notifications';

    /** @var string parent menu item identifier */
    public const PARENT_MENU_ITEM = 'woocommerce-marketing';

    /** @var string required capability to interact with page and related menu item */
    public const CAPABILITY = 'mwc_manage_email_notifications';

    /**
     * Initializes the email notifications page.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->screenId = static::SLUG;
        $this->title = __('Email Notifications', 'mwc-core');
        $this->menuTitle = __('Emails', 'mwc-core');
        $this->parentMenuSlug = static::PARENT_MENU_ITEM;
        $this->capability = static::CAPABILITY;

        parent::__construct();
    }

    /**
     * Initializes the Emails admin page.
     *
     * @throws Exception
     */
    public function load() : void
    {
        $this->registerHooks();
    }

    /**
     * Registers hooks.
     *
     * @throws Exception
     */
    protected function registerHooks() : void
    {
        Register::filter()
            ->setGroup('load-marketing_page_godaddy-email-notifications')
            ->setHandler([$this, 'registerAdminHooks'])
            ->execute();

        Register::action()
            ->setGroup('admin_enqueue_scripts')
            ->setHandler([$this, 'requireWordPressMediaUploader'])
            ->execute();
    }

    /**
     * Registers admin filters.
     *
     * @internal
     * @see EmailsPage::registerHooks()
     *
     * @throws Exception
     */
    public function registerAdminHooks() : void
    {
        Register::action()
            ->setGroup('admin_notices')
            ->setHandler([$this, 'injectBeforeNotices'])
            ->setPriority(-9999)
            ->execute();

        Register::action()
            ->setGroup('admin_notices')
            ->setHandler([$this, 'injectAfterNotices'])
            ->setPriority(PHP_INT_MAX)
            ->execute();

        Register::filter()
            ->setGroup('admin_footer_text')
            ->setHandler([$this, 'removeDefaultAdminFooter'])
            ->execute();
    }

    /**
     * Ensures that the WordPress media uploader is loaded.
     *
     * @internal
     */
    public function requireWordPressMediaUploader() : void
    {
        $screen = WordPressRepository::getCurrentScreen();

        if ($screen && 'admin-'.static::SLUG === $screen->getPageId() && version_compare(WooCommerceRepository::getWooCommerceVersion(), '6.0', '>=')) {
            wp_enqueue_media();
        }
    }

    /**
     * Renders the style tag and opens a div wrap with the class to hide the notices.
     *
     * @internal
     */
    public function injectBeforeNotices() : void
    {
        echo '<style>.skyverge-dashboard-hidden { display: none !important; } </style>',
        '<div class="skyverge-dashboard-hidden">',
        '<div class="wp-header-end"></div>';
    }

    /**
     * Closes the div wrap with the class to hide the notices.
     *
     * @internal
     */
    public function injectAfterNotices() : void
    {
        echo '</div>';
    }

    /**
     * Removes the default footer from the admin page.
     *
     * @internal
     *
     * @since x.y.z
     *
     * @return string
     */
    public function removeDefaultAdminFooter() : string
    {
        return '';
    }

    /**
     * Determines whether the menu item for the page should be added.
     *
     * @internal
     * @see AbstractAdminPage::registerMenuItem()
     *
     * @return bool
     */
    public function shouldAddMenuItem() : bool
    {
        return (bool) current_user_can($this->getCapability() ?? static::CAPABILITY);
    }

    /**
     * Returns true if the hosting plan has been upgraded recently.
     *
     * @return bool true if recently upgraded, otherwise false.
     */
    protected function recentlyUpgraded() : bool
    {
        if (! $upgraded = HostingPlanRepository::getNewInstance()->getUpgradeDateTime()) {
            return false;
        }

        return $upgraded->diff(new DateTime())->days < 30;
    }

    /**
     * Gets the title to display in the WordPress admin.
     *
     * @return string
     */
    protected function buildMenuTitle() : string
    {
        $menuTitle = $this->getMenuTitle();

        if ($this->recentlyUpgraded()) {
            $menuTitle .= '<span class="mwc-pill"><span class="mwc-pill-content">'.__('New', 'mwc-core').'</span></span>';
        }

        return $menuTitle;
    }

    /**
     * Adds the menu item.
     *
     * Overrides the parent method.
     *
     * @internal
     * @see AbstractAdminPage::registerMenuItem()
     *
     * @return self
     */
    public function addMenuItem() : AbstractAdminPage
    {
        add_submenu_page(
            $this->getParentMenuSlug(),
            $this->getTitle(),
            $this->buildMenuTitle(),
            $this->getCapability(),
            $this->getScreenId(),
            [$this, 'render']
        );

        return $this;
    }

    /**
     * Renders the Emails page HTML.
     *
     * @internal
     * @see EmailsPage::addMenuItem()
     */
    public function render() : void
    {
        ?>
        <div id="mwc-email-notifications"></div>
        <?php
    }
}
