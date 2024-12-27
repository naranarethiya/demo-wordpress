<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Enums;

use GoDaddy\WordPress\MWC\Core\Traits\EnumTrait;

class LineItemType
{
    use EnumTrait;

    public const Digital = 'DIGITAL';

    public const Physical = 'PHYSICAL';

    public const Service = 'SERVICE';

    public const Stay = 'STAY';
}
