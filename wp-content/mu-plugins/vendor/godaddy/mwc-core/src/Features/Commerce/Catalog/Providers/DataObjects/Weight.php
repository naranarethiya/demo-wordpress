<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

/**
 * Weight data object.
 *
 * This object describes the weight of a product.
 *
 * @see ShippingWeightAndDimensions
 *
 * @method static static getNewInstance(array $data)
 */
class Weight extends AbstractDataObject
{
    /** @var string weight measurement unit (e.g. `g`, `oz`...) */
    public string $unit;

    /** @var float weight measurement amount */
    public float $value;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     unit: string,
     *     value: float
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
