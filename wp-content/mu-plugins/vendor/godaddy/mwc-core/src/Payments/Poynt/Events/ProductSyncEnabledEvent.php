<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Traits\IsEventBridgeEventTrait;

/**
 * An event to broadcast when sync is enabled.
 */
class ProductSyncEnabledEvent implements EventBridgeEventContract
{
    use IsEventBridgeEventTrait;

    /** @var string */
    protected $direction;

    /**
     * Constructor.
     *
     * @param string $direction
     */
    public function __construct(string $direction)
    {
        $this->action = 'sync_enabled';
        $this->direction = $direction;
        $this->resource = 'product';
    }

    /**
     * Builds the initial data.
     *
     * @return array
     */
    protected function buildInitialData() : array
    {
        return [
            'direction'    => $this->direction,
            'providerName' => 'poynt',
        ];
    }
}
