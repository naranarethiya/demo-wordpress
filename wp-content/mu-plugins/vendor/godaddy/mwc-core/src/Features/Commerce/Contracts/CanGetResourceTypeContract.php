<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Contracts;

/**
 * Contract for resource repositories that can get the resource type.
 */
interface CanGetResourceTypeContract
{
    /**
     * Gets the resource type.
     *
     * @return string|null
     */
    public function getResourceType() : ?string;
}
