<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations;

use GoDaddy\WordPress\MWC\Common\Models\Term;
use GoDaddy\WordPress\MWC\Common\Traits\CanSeedTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\CreateOrUpdateCategoryOperationContract;

/**
 * Operation to create or update a catalog category.
 */
class CreateOrUpdateCategoryOperation implements CreateOrUpdateCategoryOperationContract
{
    use CanSeedTrait;

    /** @var Term catalog category */
    protected Term $category;

    /**
     * Sets the catalog category.
     *
     * @param Term $category
     * @return CreateOrUpdateCategoryOperationContract
     */
    public function setCategory(Term $category) : CreateOrUpdateCategoryOperationContract
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Gets the catalog category.
     *
     * @return Term
     */
    public function getCategory() : Term
    {
        return $this->category;
    }
}
