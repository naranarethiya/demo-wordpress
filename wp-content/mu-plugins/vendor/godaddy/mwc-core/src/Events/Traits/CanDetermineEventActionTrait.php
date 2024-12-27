<?php

namespace GoDaddy\WordPress\MWC\Core\Events\Traits;

use GoDaddy\WordPress\MWC\Core\Events\Enums\EventBridgeEventActionEnum;

trait CanDetermineEventActionTrait
{
    /**
     * Determines if the given values are identical.
     *
     * @param mixed $oldValue
     * @param mixed $newValue
     * @return bool
     */
    protected function isIdenticalValue($oldValue, $newValue) : bool
    {
        return $newValue === $oldValue;
    }

    /**
     * Determine the appropriate action based on the given values.
     *
     * @param mixed $oldValue
     * @param mixed $newValue
     * @return EventBridgeEventActionEnum::*
     */
    protected function determineEventAction($oldValue, $newValue) : string
    {
        if (! empty($newValue) && empty($oldValue)) {
            return EventBridgeEventActionEnum::Create;
        }

        if (empty($newValue) && ! empty($oldValue)) {
            return EventBridgeEventActionEnum::Delete;
        }

        return EventBridgeEventActionEnum::Update;
    }
}
