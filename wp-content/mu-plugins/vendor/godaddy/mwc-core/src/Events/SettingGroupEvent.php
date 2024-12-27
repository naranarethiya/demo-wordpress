<?php

namespace GoDaddy\WordPress\MWC\Core\Events;

use GoDaddy\WordPress\MWC\Common\Events\ModelEvent;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\ModelContract;

/**
 * Event to be reused by setting group classes.
 */
class SettingGroupEvent extends ModelEvent
{
    /**
     * Setting group event constructor.
     */
    public function __construct(ModelContract $model, string $action)
    {
        parent::__construct($model, 'settings', $action);
    }

    /**
     * Builds the initial data for the event, with sensitive values masked.
     *
     * @return array
     */
    protected function buildInitialData() : array
    {
        return $this->maskSensitiveInformation(parent::buildInitialData());
    }

    /**
     * Recursively masks sensitive information in all setting arrays on a given array.
     */
    protected function maskSensitiveInformation($item)
    {
        if (ArrayHelper::accessible($item)) {
            // is a setting array
            if (! empty($value = ArrayHelper::get($item, 'value'))) {
                // contains sensitive information
                if (StringHelper::contains(ArrayHelper::get($item, 'id'), [
                    'password',
                    'secret',
                    'key',
                    'token',
                ])) {
                    if (is_string($value)) {
                        $item['value'] = str_repeat('*', strlen($value));
                    } elseif (ArrayHelper::accessible($value)) {
                        foreach ($value as $key => $subValue) {
                            if (is_string($subValue)) {
                                $item['value'][$key] = str_repeat('*', strlen($subValue));
                            }
                        }
                    }
                }
            } else {
                foreach ($item as $key => $value) {
                    $item[$key] = $this->maskSensitiveInformation($value);
                }
            }
        }

        return $item;
    }
}
