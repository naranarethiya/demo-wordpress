<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses;

use GoDaddy\WordPress\MWC\Common\Contracts\FulfillmentStatusContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;

/**
 * Represents an unfulfilled fulfillment status.
 */
class UnfulfilledFulfillmentStatus implements FulfillmentStatusContract
{
    use CanConvertToArrayTrait;
    use HasLabelTrait;

    /**
     * Unfulfilled status constructor.
     *
     * Initializes the status by setting its name and label.
     */
    public function __construct()
    {
        $this->setName('unfulfilled');
        $this->setLabel(__('Unfulfilled', 'mwc-shipping'));
    }
}
