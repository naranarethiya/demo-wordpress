<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Responses;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Responses\Contracts\ListOrdersResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\HasOrdersTrait;

class ListOrdersResponse implements ListOrdersResponseContract
{
    use HasOrdersTrait;
    use CanGetNewInstanceTrait;
}
