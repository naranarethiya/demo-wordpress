<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Models;

use GoDaddy\WordPress\MWC\Common\Contracts\HasOrderIdContract;
use GoDaddy\WordPress\MWC\Common\Models\Orders\AbstractNote;
use GoDaddy\WordPress\MWC\Common\Traits\HasOrderIdTrait;

/**
 * Represents a single note from a customer. This note is supplied by the customer when the order is placed.
 */
class CustomerNote extends AbstractNote implements HasOrderIdContract
{
    use HasOrderIdTrait;
}
