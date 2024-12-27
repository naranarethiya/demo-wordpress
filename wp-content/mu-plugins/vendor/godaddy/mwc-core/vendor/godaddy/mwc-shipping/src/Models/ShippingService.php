<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models;

use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ShippingServiceContract;

/**
 * Represents a shipping service.
 *
 * @since 0.1.0
 */
class ShippingService extends AbstractModel implements ShippingServiceContract
{
    use HasLabelTrait;
}
