<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts;

interface HasSortingContract
{
    /**
     * Set the sort by.
     *
     * @param ?string $sortBy
     * @return $this
     */
    public function setSortBy(?string $sortBy);

    /**
     * Get the sort by.
     *
     * @return ?string
     */
    public function getSortBy() : ?string;

    /**
     * Set the sort order.
     *
     * @param ?string $sortOrder
     * @return $this
     */
    public function setSortOrder(?string $sortOrder);

    /**
     * Get the sort order.
     *
     * @return ?string
     */
    public function getSortOrder() : ?string;
}
