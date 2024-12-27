<?php

namespace GoDaddy\WordPress\MWC\Core\Settings\Models;

use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Events\ModelEvent;
use GoDaddy\WordPress\MWC\Common\Settings\Models\SettingGroup as CommonSettingGroup;
use GoDaddy\WordPress\MWC\Core\Events\SettingGroupEvent;

/**
 * An object model for representing a setting group.
 */
class SettingGroup extends CommonSettingGroup
{
    /**
     * Updates the settings in the setting group.
     *
     * This method also broadcast model events.
     *
     * @return self
     */
    public function update()
    {
        parent::update();

        Events::broadcast($this->buildEvent('settings', 'update'));

        return $this;
    }

    /**
     * Builds a model event, using the SettingGroupEvent to mask sensitive information.
     *
     * @param string $resource
     * @param string $action
     * @return ModelEvent
     */
    protected function buildEvent(string $resource, string $action) : ModelEvent
    {
        return new SettingGroupEvent($this, $action);
    }
}
