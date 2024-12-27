<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Operations;

use GoDaddy\WordPress\MWC\Common\Traits\CanSeedTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Operations\Contracts\ListOrdersByIdOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\HasLocalIdsTrait;

class ListOrdersByIdOperation implements ListOrdersByIdOperationContract
{
    use CanSeedTrait;
    use HasLocalIdsTrait;
}
