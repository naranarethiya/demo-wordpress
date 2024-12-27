<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Enums;

use GoDaddy\WordPress\MWC\Core\Traits\EnumTrait;

class PaymentStatus
{
    use EnumTrait;

    public const None = 'NONE';

    public const Processing = 'PROCESSING';

    public const PartiallyPaid = 'PARTIALLY_PAID';

    public const Paid = 'PAID';

    public const PartiallyRefunded = 'PARTIALLY_REFUNDED';

    public const Refunded = 'REFUNDED';

    public const Canceled = 'CANCELED';

    public const Pending = 'PENDING';

    public const ExternallyProcessed = 'EXTERNALLY_PROCESSED';

    public const Declined = 'DECLINED';
}
