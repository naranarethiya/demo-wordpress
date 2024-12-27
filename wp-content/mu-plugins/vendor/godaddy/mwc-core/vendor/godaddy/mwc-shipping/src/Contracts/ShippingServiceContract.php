<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\HasLabelContract;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\ModelContract;

/**
 * The shipping service contract.
 */
interface ShippingServiceContract extends ModelContract, HasLabelContract
{
}
