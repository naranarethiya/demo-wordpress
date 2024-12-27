<?php

namespace GoDaddy\WordPress\MWC\Core\Features\PluginControls\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;

/**
 * Prevents plugin actions from firing on locked plugins.
 */
class RemovePluginActionLinksInterceptor extends AbstractLockedPluginInterceptor
{
    /** @var array<string, bool> */
    protected const ACTIONS_TO_REMOVE = [
        'deactivate' => true,
        'delete'     => true,
    ];

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function addHooks() : void
    {
        $this->registerLockedPluginHooks();
    }

    /**
     * Registers removeActionLinks hook on each locked plugin.
     *
     * @return void
     * @throws Exception
     */
    protected function registerLockedPluginHooks() : void
    {
        foreach ($this->getLockedPlugins() as $plugin) {
            $basename = ArrayHelper::get(ArrayHelper::wrap($plugin), 'basename');
            if (! empty($basename) && is_string($basename)) {
                Register::filter()
                    ->setGroup("plugin_action_links_{$basename}")
                    ->setHandler([$this, 'removeActionLinks'])
                    ->setPriority(PHP_INT_MAX)
                    ->execute();
            }
        }
    }

    /**
     * Removes invalid plugin actions.
     *
     * @param mixed $actions The actions to link for this plugin.
     * @return mixed[] The filtered $actions value.
     */
    public function removeActionLinks($actions) : array
    {
        if (! is_array($actions)) {
            return [];
        }

        return array_diff_key($actions, static::ACTIONS_TO_REMOVE);
    }
}
