<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\Category;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\ListCategoriesResponseContract;

/**
 * Response object for a list categories request.
 *
 * @method static static getNewInstance(Category[] $categories)
 */
class ListCategoriesResponse implements ListCategoriesResponseContract
{
    use CanGetNewInstanceTrait;

    /** @var Category[] */
    protected array $categories;

    /**
     * Constructor.
     *
     * @param Category[] $categories
     */
    public function __construct(array $categories)
    {
        $this->categories = $categories;
    }

    /**
     * {@inheritDoc}
     */
    public function setCategories(array $value) : ListCategoriesResponseContract
    {
        $this->categories = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getCategories() : array
    {
        return $this->categories;
    }
}
