<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Enums;

use GoDaddy\WordPress\MWC\Core\Traits\EnumTrait;

class NoteAuthorType
{
    use EnumTrait;

    public const Customer = 'CUSTOMER';

    public const Merchant = 'MERCHANT';

    public const None = 'NONE';
}
