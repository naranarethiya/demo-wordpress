<?php

namespace GoDaddy\WordPress\MWC\Core\Features\PluginControls\Interceptors\Handlers;

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\RemoteInboxNotificationsDataSourcePoller;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\Features\PluginControls\Interceptors\WooCommerceUpdatesInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\PluginControls\Traits\CanIdentifyLockedPluginsTrait;
use stdClass;

/**
 * Handler to exclude `update` notes from store alerts. This ensures sites that have WooCommerce updates managed for
 * them do not receive store alerts about updating WooCommerce in their dashboard.
 * @see WooCommerceUpdatesInterceptor
 */
class RemoveStoreUpdateAlertsHandler extends AbstractInterceptorHandler
{
    use CanIdentifyLockedPluginsTrait;

    /**
     * Filter callback for {@see RemoteInboxNotificationsDataSourcePoller::get_specs_from_data_sources()}.
     *
     * @param ...$args
     * @return array<string, stdClass>|mixed
     */
    public function run(...$args)
    {
        /** @var array<string, stdClass>|mixed $specs */
        $specs = ArrayHelper::get($args, 0);

        /** @var string|mixed $pollerId */
        $pollerId = ArrayHelper::get($args, 1);

        if (! is_array($specs) || ! $this->isInboxNotificationsPoller($pollerId) || ! $this->isWooCommerceLocked()) {
            return $specs;
        }

        return $this->removeUpdateNotifications($specs);
    }

    /**
     * Determines whether the supplied poller ID is the "remote inbox notifications" poller.
     *
     * @param mixed $pollerId
     * @return bool
     */
    protected function isInboxNotificationsPoller($pollerId) : bool
    {
        $expectedId = class_exists(RemoteInboxNotificationsDataSourcePoller::class) ? RemoteInboxNotificationsDataSourcePoller::ID : 'remote_inbox_notifications';

        return $pollerId === $expectedId;
    }

    /**
     * Determines whether the WooCommerce plugin is locked.
     *
     * @return bool
     */
    protected function isWooCommerceLocked() : bool
    {
        return $this->isPluginLocked('woocommerce/woocommerce.php');
    }

    /**
     * Removes "update" type alerts from the specs.
     *
     * @param array<string, stdClass> $specs
     * @return array<string, stdClass>
     */
    protected function removeUpdateNotifications(array $specs) : array
    {
        return array_filter($specs, function ($spec) {
            return ($spec->type ?? null) !== 'update';
        });
    }
}
