<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\Category;

/**
 * Contract for responses when listing categories.
 */
interface ListCategoriesResponseContract
{
    /**
     * Sets the categories for the response.
     *
     * @param Category[] $value
     * @return ListCategoriesResponseContract
     */
    public function setCategories(array $value) : ListCategoriesResponseContract;

    /**
     * Gets the categories for the response.
     *
     * @return Category[]
     */
    public function getCategories() : array;

    // @TODO in the future we may have pagination methods here
}
