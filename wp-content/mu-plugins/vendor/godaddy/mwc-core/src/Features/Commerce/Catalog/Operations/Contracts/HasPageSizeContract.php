<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts;

interface HasPageSizeContract
{
    /**
     * Sets the number of results per page.
     *
     * @param int|null $value
     * @return $this
     */
    public function setPageSize(?int $value);

    /**
     * Gets the number of results per page.
     *
     * @return int|null
     */
    public function getPageSize() : ?int;
}
