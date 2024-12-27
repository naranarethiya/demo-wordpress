<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts;

use Exception;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;

interface ResourceWriteServiceContract
{
    /**
     * Writes the local resource to the Commerce platform.
     *
     * @param object $localResource
     * @return mixed
     * @throws Exception|CommerceExceptionContract Should throw an exception upon write failure.
     */
    public function write(object $localResource);
}
