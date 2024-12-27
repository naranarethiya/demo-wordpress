<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Level;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\CreateOrUpdateLevelResponseContract;

abstract class AbstractLevelResponse implements CreateOrUpdateLevelResponseContract
{
    protected Level $level;

    /**
     * @param Level $level
     */
    public function __construct(Level $level)
    {
        $this->level = $level;
    }

    /**
     * {@inheritDoc}
     */
    public function getLevel() : Level
    {
        return $this->level;
    }

    /**
     * {@inheritDoc}
     */
    public function setLevel(Level $value) : AbstractLevelResponse
    {
        $this->level = $value;

        return $this;
    }
}
