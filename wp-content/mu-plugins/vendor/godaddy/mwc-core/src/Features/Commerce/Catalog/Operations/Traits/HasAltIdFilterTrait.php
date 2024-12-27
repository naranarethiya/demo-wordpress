<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Traits;

trait HasAltIdFilterTrait
{
    /** @var ?string the altID (aka slug) */
    protected ?string $altId = null;

    /**
     * {@inheritDoc}
     */
    public function setAltId(?string $altId)
    {
        $this->altId = $altId;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getAltId() : ?string
    {
        return $this->altId;
    }
}
