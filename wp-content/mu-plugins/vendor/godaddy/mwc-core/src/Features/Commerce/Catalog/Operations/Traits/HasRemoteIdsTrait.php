<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Traits;

trait HasRemoteIdsTrait
{
    /** @var string[]|null the remote (Commerce) resource IDs to include */
    protected ?array $ids = null;

    /**
     * {@inheritDoc}
     */
    public function setIds(?array $value)
    {
        $this->ids = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getIds() : ?array
    {
        return $this->ids;
    }
}
