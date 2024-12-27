<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Traits;

trait HasPageSizeTrait
{
    /** @var int|null maximum number of results per page */
    protected ?int $pageSize = null;

    /**
     * {@inheritDoc}
     */
    public function setPageSize(?int $value)
    {
        $this->pageSize = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getPageSize() : ?int
    {
        return $this->pageSize;
    }
}
