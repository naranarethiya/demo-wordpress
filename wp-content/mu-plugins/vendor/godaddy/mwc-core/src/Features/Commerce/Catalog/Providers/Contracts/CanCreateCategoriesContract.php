<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Exceptions\CategoryAltIdNotUniqueException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\Category;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\CategoryRequestInputs\CreateCategoryInput;

/**
 * Contract for providers that can create a {@see Category}.
 */
interface CanCreateCategoriesContract
{
    /**
     * Creates a {@see Category}.
     *
     * @param CreateCategoryInput $input
     * @return Category
     * @throws CategoryAltIdNotUniqueException
     */
    public function create(CreateCategoryInput $input) : Category;
}
