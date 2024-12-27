<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Exceptions\CategoryAltIdNotUniqueException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\Category;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\CategoryRequestInputs\UpdateCategoryInput;

/**
 * Contract for providers that can update a {@see Category}.
 */
interface CanUpdateCategoriesContract
{
    /**
     * Updates a {@see Category}.
     *
     * @param UpdateCategoryInput $input
     * @return Category
     * @throws CategoryAltIdNotUniqueException
     */
    public function update(UpdateCategoryInput $input) : Category;
}
