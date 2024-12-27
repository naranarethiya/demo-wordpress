<?php

namespace GoDaddy\WordPress\MWC\Core\Events;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\ModelEvent;
use GoDaddy\WordPress\MWC\Common\Extensions\Types\PluginExtension;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * Plugin lifecycle event.
 *
 * @method static static getNewInstance(PluginExtension $plugin, string $action)
 * @property PluginExtension $model
 */
class PluginLifecycleEvent extends ModelEvent
{
    use CanGetNewInstanceTrait;

    /**
     * Builds the event.
     *
     * @param PluginExtension $plugin the plugin
     * @param string $action plugin lifecycle action
     */
    public function __construct(PluginExtension $plugin, string $action)
    {
        parent::__construct($plugin, 'plugin', $action);
    }

    /**
     * Builds the initial event data.
     *
     * @return array<string, mixed>
     * @throws Exception
     */
    public function buildInitialData() : array
    {
        $initialData = parent::buildInitialData();
        // set a flag whether the plugin resource is a blocked plugin
        $initialData['resource'] = ArrayHelper::combine($initialData['resource'], [
            'blocked' => $this->model->isBlocked(),
        ]);

        return ArrayHelper::combine($initialData, [
            'action' => $this->action,
            'plugin' => $initialData['resource'],
        ]);
    }
}
