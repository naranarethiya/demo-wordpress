<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

/**
 * Dimensions data object.
 *
 * This object describes the dimensions of a product.
 *
 * @see ShippingWeightAndDimensions
 *
 * @method static static getNewInstance(array $data)
 */
class Dimensions extends AbstractDataObject
{
    /** @var float */
    public float $height;

    /** @var float */
    public float $length;

    /** @var float */
    public float $width;

    /** @var string dimensions measurement unit (e.g. `m`, `in`...) */
    public string $unit;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     height: float,
     *     length: float,
     *     unit: string,
     *     width: float,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
