<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Enums;

use GoDaddy\WordPress\MWC\Core\Traits\EnumTrait;

class LineItemMode
{
    use EnumTrait;

    public const Curbside = 'CURBSIDE';

    public const Delivery = 'DELIVERY';

    public const Digital = 'DIGITAL';

    public const DriveThru = 'DRIVE_THRU';

    public const ForHere = 'FOR_HERE';

    public const GeneralContainer = 'GENERAL_CONTAINER';

    public const GiftCard = 'GIFT_CARD';

    public const None = 'NONE';

    public const NonLodgingNrr = 'NON_LODGING_NRR';

    public const NonLodgingSale = 'NON_LODGING_SALE';

    public const Pickup = 'PICKUP';

    public const Purchase = 'PURCHASE';

    public const QuickStay = 'QUICK_STAY';

    public const RegularStay = 'REGULAR_STAY';

    public const Ship = 'SHIP';

    public const ToGo = 'TO_GO';
}
