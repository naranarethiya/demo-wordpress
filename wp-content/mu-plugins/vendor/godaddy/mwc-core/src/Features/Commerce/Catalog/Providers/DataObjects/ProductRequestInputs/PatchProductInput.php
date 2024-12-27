<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductRequestInputs;

class PatchProductInput extends AbstractProductInput
{
    /** @var string remote product UUID */
    public string $productId;

    /** @var array<string, mixed> properties to patch */
    public array $properties;

    /**
     * Constructor.
     *
     * @param array{
     *     productId: string,
     *     properties: array<string, mixed>,
     *     storeId: string
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
