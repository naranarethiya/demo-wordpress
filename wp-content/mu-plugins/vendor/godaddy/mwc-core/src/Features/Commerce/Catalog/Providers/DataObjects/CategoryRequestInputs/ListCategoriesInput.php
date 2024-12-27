<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\CategoryRequestInputs;

/**
 * Input data object for listing categories.
 */
class ListCategoriesInput extends AbstractCategoryInput
{
    /** @var array<string, mixed> the query args */
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
