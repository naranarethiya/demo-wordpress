<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts;

use GoDaddy\WordPress\MWC\Common\Models\Term;

/**
 * Contract for creating or updating category operations.
 */
interface CreateOrUpdateCategoryOperationContract
{
    /**
     * Sets the local Category model.
     *
     * @param Term $category
     * @return CreateOrUpdateCategoryOperationContract
     */
    public function setCategory(Term $category) : CreateOrUpdateCategoryOperationContract;

    /**
     * Gets the local Category model.
     *
     * @return Term
     */
    public function getCategory() : Term;
}
