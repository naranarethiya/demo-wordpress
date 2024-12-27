<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses;

use GoDaddy\WordPress\MWC\Common\Contracts\FulfillmentStatusContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;

/**
 * Represents a fulfilled fulfillment status.
 */
class FulfilledFulfillmentStatus implements FulfillmentStatusContract
{
    use CanConvertToArrayTrait;
    use HasLabelTrait;

    /**
     * Fulfilled status constructor.
     *
     * Initializes the status by setting its name and label.
     */
    public function __construct()
    {
        $this->setName('fulfilled');
        $this->setLabel(__('Fulfilled', 'mwc-shipping'));
    }
}
