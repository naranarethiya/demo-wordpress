<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Assets\Enums;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Assets\Traits\CanTryCropPositionEnumFromArrayTrait;

class YCropPosition
{
    use CanTryCropPositionEnumFromArrayTrait;

    public const Bottom = 'bottom';

    public const Center = 'center';

    public const Top = 'top';
}
