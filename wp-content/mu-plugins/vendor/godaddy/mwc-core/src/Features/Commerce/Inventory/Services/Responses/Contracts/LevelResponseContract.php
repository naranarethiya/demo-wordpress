<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Level;

interface LevelResponseContract
{
    /**
     * Gets the level.
     *
     * @return Level
     */
    public function getLevel() : Level;

    /**
     * Sets the level.
     *
     * @param Level $value
     *
     * @return $this
     */
    public function setLevel(Level $value) : self;
}
