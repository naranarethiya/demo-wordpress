<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\Category;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\ReadCategoryResponseContract;

/**
 * Response object for a read category request.
 *
 * @method static static getNewInstance(Category $category)
 */
class ReadCategoryResponse implements ReadCategoryResponseContract
{
    use CanGetNewInstanceTrait;

    /** @var Category */
    protected Category $category;

    /**
     * Constructor.
     *
     * @param Category $category
     */
    public function __construct(Category $category)
    {
        $this->category = $category;
    }

    /**
     * Sets the category.
     *
     * @param Category $value
     * @return $this
     */
    public function setCategory(Category $value) : ReadCategoryResponseContract
    {
        $this->category = $value;

        return $this;
    }

    /**
     * Gets the category.
     *
     * @return Category
     */
    public function getCategory() : Category
    {
        return $this->category;
    }
}
