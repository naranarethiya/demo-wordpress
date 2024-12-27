<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Assets\Enums;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Assets\Traits\CanTryCropPositionEnumFromArrayTrait;

class XCropPosition
{
    use CanTryCropPositionEnumFromArrayTrait;

    public const Center = 'center';

    public const Left = 'left';

    public const Right = 'right';
}
