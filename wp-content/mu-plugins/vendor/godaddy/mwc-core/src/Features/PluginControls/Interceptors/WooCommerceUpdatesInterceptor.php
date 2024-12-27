<?php

namespace GoDaddy\WordPress\MWC\Core\Features\PluginControls\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\PluginControls\Interceptors\Handlers\RemoveStoreUpdateAlertsHandler;
use WC_Notes_Run_Db_Update;

/**
 * Prevents updates from WooCommerce.
 */
class WooCommerceUpdatesInterceptor extends AbstractInterceptor
{
    /**
     * Registers interceptor's actions and filters.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'unregisterDatabaseUpdateNote'])
            ->setPriority(-PHP_INT_MAX)
            ->execute();

        Register::filter()
            ->setGroup('woocommerce_show_admin_notice')
            ->setHandler([$this, 'shouldShowAdminNotice'])
            ->setArgumentsCount(2)
            ->setPriority(PHP_INT_MAX)
            ->execute();

        Register::filter()
            ->setGroup('data_source_poller_specs')
            ->setHandler([RemoveStoreUpdateAlertsHandler::class, 'handle'])
            ->setArgumentsCount(2)
            ->setPriority(PHP_INT_MAX)
            ->execute();
    }

    /**
     * Unregisters the action for showing the WooCommerce run database updater notice.
     *
     * @return void
     * @throws Exception
     */
    public function unregisterDatabaseUpdateNote() : void
    {
        if (! class_exists('WC_Notes_Run_Db_Update') ||
            ! method_exists(WC_Notes_Run_Db_Update::class, 'show_reminder')) {
            return;
        }

        Register::action()
            ->setGroup('current_screen')
            ->setHandler([WC_Notes_Run_Db_Update::class, 'show_reminder'])
            ->deregister();
    }

    /**
     * Determines if the notice should be shown or not based on what type of notice.
     *
     * @param bool $shouldShow
     * @param string $noticeName
     * @return bool
     */
    public function shouldShowAdminNotice(bool $shouldShow, string $noticeName) : bool
    {
        return 'update' === $noticeName ? false : $shouldShow;
    }
}
