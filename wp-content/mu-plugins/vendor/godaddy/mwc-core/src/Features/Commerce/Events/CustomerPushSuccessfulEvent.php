<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Events;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Events\Traits\HasJobTrait;

class CustomerPushSuccessfulEvent extends AbstractCustomerPushEvent
{
    use HasJobTrait;
    use CanGetNewInstanceTrait;
}
