<?php

namespace GoDaddy\WordPress\MWC\Core\Events\Site;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Common\Traits\IsEventBridgeEventTrait;
use GoDaddy\WordPress\MWC\Core\Events\Enums\EventBridgeEventActionEnum;

/**
 * @method static static getNewInstance(string $action = EventBridgeEventActionEnum::Customize)
 */
abstract class AbstractSiteOptionEvent implements EventBridgeEventContract
{
    use IsEventBridgeEventTrait;
    use CanGetNewInstanceTrait;

    /**
     * Constructor.
     *
     * @param EventBridgeEventActionEnum::* $action
     */
    public function __construct(string $action = EventBridgeEventActionEnum::Customize)
    {
        $this->resource = $this->resourceName();
        $this->action = $action;
    }

    /**
     * Defines event's resource name.
     *
     * @return string
     */
    abstract protected function resourceName() : string;
}
