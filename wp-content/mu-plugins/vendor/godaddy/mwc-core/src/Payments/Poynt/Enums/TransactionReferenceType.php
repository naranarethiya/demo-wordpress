<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Enums;

use GoDaddy\WordPress\MWC\Core\Traits\EnumTrait;

class TransactionReferenceType
{
    use EnumTrait;

    public const CentralOrder = 'CENTRAL_ORDER';
    public const Custom = 'CUSTOM';
    public const PoyntOrder = 'POYNT_ORDER';
}
