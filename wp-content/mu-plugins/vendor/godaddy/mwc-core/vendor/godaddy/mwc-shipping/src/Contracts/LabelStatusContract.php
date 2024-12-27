<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\CanConvertToArrayContract;
use GoDaddy\WordPress\MWC\Common\Contracts\HasLabelContract;

/**
 * Shipping label status contract.
 */
interface LabelStatusContract extends HasLabelContract, CanConvertToArrayContract
{
}
