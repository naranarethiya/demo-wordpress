<?php

namespace GoDaddy\WordPress\MWC\Core\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Traits\IsEventBridgeEventTrait;

/**
 * Shipping zone method added event class.
 */
class ShippingZoneMethodAddedEvent implements EventBridgeEventContract
{
    use IsEventBridgeEventTrait;

    /** @var int The ID of the shipping zone that the shipping method was added to */
    protected $shippingZoneId;

    /** @var string The type of the shipping method */
    protected $shippingMethodType;

    /**
     * Event constructor.
     *
     * @param int $shippingZoneId
     * @param string $shippingMethodType
     */
    public function __construct(int $shippingZoneId, string $shippingMethodType)
    {
        $this->resource = 'shipping_zone_method';
        $this->action = 'create';
        $this->shippingZoneId = $shippingZoneId;
        $this->shippingMethodType = $shippingMethodType;
    }

    /**
     * Builds the initial data for the event.
     *
     * @return array
     */
    protected function buildInitialData() : array
    {
        return [
            'shippingZone' => [
                'id' => $this->shippingZoneId,
            ],
            'shippingMethod' => [
                'type' => $this->shippingMethodType,
            ],
        ];
    }
}
