<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\Category;

/**
 * Contract for responses when reading a category.
 */
interface ReadCategoryResponseContract
{
    /**
     * Sets the {@see Category} for the response.
     *
     * @param Category $value
     * @return ReadCategoryResponseContract
     */
    public function setCategory(Category $value) : ReadCategoryResponseContract;

    /**
     * Gets the {@see Category} from the response.
     *
     * @return Category
     */
    public function getCategory() : Category;
}
