<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductRequestInputs;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;

/**
 * Base data object for updating a product.
 *
 * @method static static getNewInstance(array $data)
 */
class UpdateProductInput extends AbstractProductInput
{
    /** @var ProductBase */
    public ProductBase $product;

    /**
     * Constructor.
     *
     * @param array{
     *     product: ProductBase,
     *     storeId: string
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
