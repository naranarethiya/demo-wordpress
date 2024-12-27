<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts;

/**
 * Create or update category response contract.
 */
interface CreateOrUpdateCategoryResponseContract
{
    /**
     * Gets the category's remote UUID.
     *
     * @return non-empty-string
     */
    public function getRemoteId() : string;
}
