<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\CategoryRequestInputs;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\Category;

/**
 * Input data object for updating a category.
 */
class UpdateCategoryInput extends AbstractCategoryInput
{
    /** @var Category */
    public Category $category;

    /**
     *  Constructor.
     *
     * @param array{
     *     category: Category,
     *     storeId: string,
     *     } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
