<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

/**
 * Shipping weight and dimensions data object.
 *
 * This object describes the shipping weight and dimensions of a product.
 *
 * @see Dimensions
 * @see Weight
 *
 * @method static static getNewInstance(array $data)
 */
class ShippingWeightAndDimensions extends AbstractDataObject
{
    /** @var Dimensions */
    public Dimensions $dimensions;

    /** @var Weight */
    public Weight $weight;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     dimensions: Dimensions,
     *     weight: Weight,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
