<?php

namespace GoDaddy\WordPress\MWC\Shipping\Operations;

use GoDaddy\WordPress\MWC\Shipping\Contracts\VoidShippingLabelOperationContract;
use GoDaddy\WordPress\MWC\Shipping\Traits\HasAccountTrait;
use GoDaddy\WordPress\MWC\Shipping\Traits\HasPackageTrait;

class VoidShippingLabelOperation implements VoidShippingLabelOperationContract
{
    use HasAccountTrait;
    use HasPackageTrait;
}
