<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Traits;

trait HasParentIdTrait
{
    /** @var ?string the parent id */
    protected ?string $parentId = null;

    /**
     * {@inheritDoc}
     */
    public function getParentId() : ?string
    {
        return $this->parentId;
    }

    /**
     * {@inheritDoc}
     */
    public function setParentId(?string $parentId)
    {
        $this->parentId = $parentId;

        return $this;
    }
}
