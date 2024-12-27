<?php

namespace GoDaddy\WordPress\MWC\Core\Features\PluginControls\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;

/**
 * Prevents updates from WordPress.
 *
 * @todo consider moving this to a different namespace, this blocks WP Core updates and is not related to controlling plugins.
 */
class UpdatesInterceptor extends AbstractInterceptor
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
            ->setGroup('load-update-core.php')
            ->setHandler([$this, 'registerDashboardUpdatesHooks'])
            ->execute();

        Register::action()
            ->setGroup('in_admin_header')
            ->setHandler([$this, 'unregisterUpdateNotices'])
            ->setPriority(PHP_INT_MAX)
            ->execute();

        Register::filter()
            ->setGroup('user_has_cap')
            ->setHandler([$this, 'disableUpdateCoreCapability'])
            ->setPriority(PHP_INT_MAX)
            ->execute();

        Register::filter()
            ->setGroup('auto_update_core')
            ->setHandler('__return_false')
            ->setPriority(PHP_INT_MAX)
            ->execute();

        Register::filter()
            ->setGroup('site_status_tests')
            ->setHandler([$this, 'removeSiteStatusTests'])
            ->setPriority(PHP_INT_MAX)
            ->execute();

        $this->registerDisableUpdateEmailNotificationsFilters();
    }

    /**
     * Registers dashboard update notifications.
     *
     * @return void
     * @throws Exception
     */
    public function registerDashboardUpdatesHooks() : void
    {
        Register::action()
            ->setGroup('admin_print_styles')
            ->setHandler([$this, 'renderStyleOverrides'])
            ->execute();
    }

    /**
     * Renders the style tag to hide content via CSS.
     *
     * @return void
     */
    public function renderStyleOverrides() : void
    {
        echo '<style>.update-last-checked{display:none!important;}</style>';
    }

    /**
     * Unregisters update notices.
     *
     * @return void
     * @throws Exception
     */
    public function unregisterUpdateNotices() : void
    {
        $adminNoticesHooks = ['user_admin_notices', 'admin_notices', 'all_admin_notices'];

        if (WordPressRepository::isNetworkAdminRequest()) {
            $adminNoticesHooks[] = 'network_admin_notices';
        }

        // list of admin notices hooks
        foreach ($adminNoticesHooks as $hook) {
            $this->unregisterHookUpdateNotices($hook);
        }
    }

    /**
     * Unregisters update notices actions for the given hook.
     *
     * @param string $hook
     * @return void
     * @throws Exception
     */
    protected function unregisterHookUpdateNotices(string $hook) : void
    {
        $coreUpdateNotices = ['update_nag' => 3, 'maintenance_nag' => 10];

        if (WordPressRepository::isMultisite()) {
            $coreUpdateNotices['site_admin_notice'] = 10;
        }

        foreach ($coreUpdateNotices as $handler => $priority) {
            Register::action()
                ->setGroup($hook)
                ->setHandler($handler)
                ->setPriority($priority)
                ->deregister();
        }
    }

    /**
     * Disables update core capability/permission.
     *
     * @param array<bool> $capabilities
     * @return array<bool>
     */
    public function disableUpdateCoreCapability(array $capabilities) : array
    {
        $capabilities['update_core'] = false;

        return $capabilities;
    }

    /**
     * Exclude certain site status tests from running.
     *
     * @param mixed $tests Site status tests.
     * @return mixed Filtered list of site status tests to run.
     */
    public function removeSiteStatusTests($tests)
    {
        if (! is_array($tests)) {
            return $tests;
        }

        if (ArrayHelper::has($tests, 'async.background_updates')) {
            ArrayHelper::remove($tests['async'], ['background_updates']);
        }

        if (ArrayHelper::has($tests, 'direct.wordpress_version')) {
            ArrayHelper::remove($tests['direct'], ['wordpress_version']);
        }

        return $tests;
    }

    /**
     * Register filters to disable core update email notifications.
     *
     * @return void
     * @throws Exception
     */
    protected function registerDisableUpdateEmailNotificationsFilters() : void
    {
        $updateEmailNotificationsHooks = [
            'automatic_updates_send_email',
            'enable_auto_upgrade_email',
            'automatic_updates_send_debug_email',
            'auto_core_update_send_email',
            'send_core_update_notification_email',
        ];

        foreach ($updateEmailNotificationsHooks as $hook) {
            Register::filter()
                ->setGroup($hook)
                ->setHandler('__return_false')
                ->setPriority(PHP_INT_MAX)
                ->execute();
        }
    }
}
