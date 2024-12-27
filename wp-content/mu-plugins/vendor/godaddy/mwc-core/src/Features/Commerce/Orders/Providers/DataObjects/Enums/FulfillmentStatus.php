<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Enums;

use GoDaddy\WordPress\MWC\Core\Traits\EnumTrait;

class FulfillmentStatus
{
    use EnumTrait;

    public const Unfulfilled = 'UNFULFILLED';

    public const OnHold = 'ON_HOLD';

    public const InProgress = 'IN_PROGRESS';

    public const PartiallyFulfilled = 'PARTIALLY_FULFILLED';

    public const Fulfilled = 'FULFILLED';

    public const PartiallyReturned = 'PARTIALLY_RETURNED';

    public const Returned = 'RETURNED';

    public const Awaiting = 'AWAITING';

    public const Confirmed = 'CONFIRMED';

    public const Canceled = 'CANCELED';
}
