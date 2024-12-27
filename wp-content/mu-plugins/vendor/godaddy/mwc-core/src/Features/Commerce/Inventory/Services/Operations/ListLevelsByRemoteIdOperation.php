<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations;

use GoDaddy\WordPress\MWC\Common\Traits\CanSeedTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\ListLevelsByRemoteIdOperationContract;

class ListLevelsByRemoteIdOperation implements ListLevelsByRemoteIdOperationContract
{
    use CanSeedTrait;

    /** @var string[]|null remote product IDs */
    protected ?array $ids = null;

    /**
     * {@inheritDoc}
     */
    public function getIds() : ?array
    {
        return $this->ids;
    }

    /**
     * {@inheritDoc}
     */
    public function setIds(?array $value) : ListLevelsByRemoteIdOperationContract
    {
        $this->ids = $value;

        return $this;
    }
}
