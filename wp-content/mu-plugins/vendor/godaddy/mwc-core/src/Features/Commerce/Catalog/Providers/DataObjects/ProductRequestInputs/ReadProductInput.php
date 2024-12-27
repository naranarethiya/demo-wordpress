<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductRequestInputs;

/**
 * Base data object for reading a product.
 *
 * @method static static getNewInstance(array $data)
 */
class ReadProductInput extends AbstractProductInput
{
    /** @var string this is the remote product UUID */
    public string $productId;

    /**
     * Constructor.
     *
     * @param array{
     *     productId: string,
     *     storeId: string,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
