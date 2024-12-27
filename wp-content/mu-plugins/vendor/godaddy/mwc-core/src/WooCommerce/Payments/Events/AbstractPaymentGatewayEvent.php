<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Traits\IsEventBridgeEventTrait;

/**
 * Abstract payment gateway event.
 */
class AbstractPaymentGatewayEvent implements EventBridgeEventContract
{
    use IsEventBridgeEventTrait;

    /** @var string The ID of the payment gateway */
    protected $id;

    /**
     * Constructor.
     *
     * @param string $id call to action ID
     */
    public function __construct(string $id)
    {
        $this->resource = 'payment_gateway';
        $this->id = $id;
    }

    /**
     * Builds the initial data for the event.
     *
     * @return array
     */
    protected function buildInitialData() : array
    {
        return [
            'paymentGateway' => [
                'id' => $this->id,
            ],
        ];
    }
}
