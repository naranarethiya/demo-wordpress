<?php

namespace GoDaddy\WordPress\MWC\Core\Features\PluginControls\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;

/**
 * Prevents locked plugins from being updated directly through WordPress.
 */
class RemovePluginsFromUpdatesListInterceptor extends AbstractLockedPluginInterceptor
{
    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::filter()
            ->setPriority(PHP_INT_MAX)
            ->setGroup('pre_set_site_transient_update_plugins')
            ->setHandler([$this, 'removePluginsFromUpdatesList'])
            ->execute();
    }

    /**
     * Removes blocked plugins from the list of update-able plugins.
     *
     * @param mixed $list The unfiltered list of plugins that need updated.
     *
     * @return mixed The filtered $list value
     */
    public function removePluginsFromUpdatesList($list)
    {
        if (is_object($list) && property_exists($list, 'response')) {
            $list->response = array_diff_key(ArrayHelper::wrap($list->response), array_flip(ArrayHelper::pluck($this->getLockedPlugins(), 'basename')));
        }

        return $list;
    }
}
