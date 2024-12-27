<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Level;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\ListLevelsResponseContract;

class ListLevelsResponse implements ListLevelsResponseContract
{
    use CanGetNewInstanceTrait;

    /** @var Level[] */
    protected array $levels;

    /**
     * @param Level[] $levels
     */
    public function __construct(array $levels)
    {
        $this->levels = $levels;
    }

    /**
     * Gets the levels.
     *
     * @return Level[]
     */
    public function getLevels() : array
    {
        return $this->levels;
    }
}
