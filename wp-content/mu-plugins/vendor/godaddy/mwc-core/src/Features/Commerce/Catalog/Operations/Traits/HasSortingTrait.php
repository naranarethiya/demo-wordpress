<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Traits;

trait HasSortingTrait
{
    /** @var ?string sort by */
    protected ?string $sortBy = null;

    /** @var ?string the sort order */
    protected ?string $sortOrder = null;

    /**
     * {@inheritDoc}
     */
    public function setSortBy(?string $sortBy)
    {
        $this->sortBy = $sortBy;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSortBy() : ?string
    {
        return $this->sortBy;
    }

    /**
     * {@inheritDoc}
     */
    public function setSortOrder(?string $sortOrder)
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSortOrder() : ?string
    {
        return $this->sortOrder;
    }
}
