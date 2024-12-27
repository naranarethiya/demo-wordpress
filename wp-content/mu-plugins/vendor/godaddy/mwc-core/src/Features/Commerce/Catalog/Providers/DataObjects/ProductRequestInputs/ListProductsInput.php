<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductRequestInputs;

class ListProductsInput extends AbstractProductInput
{
    /** @var array<mixed> the query args */
    public array $queryArgs;

    /**
     * Constructor.
     *
     * @param array{
     *     queryArgs: array<mixed>,
     *     storeId: string
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
