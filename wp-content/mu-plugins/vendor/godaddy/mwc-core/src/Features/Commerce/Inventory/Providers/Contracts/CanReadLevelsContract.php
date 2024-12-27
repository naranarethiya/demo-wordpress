<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Level;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ReadLevelInput;

interface CanReadLevelsContract
{
    /**
     * Reads the item level.
     *
     * @param ReadLevelInput $input
     *
     * @return Level
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    public function read(ReadLevelInput $input) : Level;
}
