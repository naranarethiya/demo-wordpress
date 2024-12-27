<?php

namespace GoDaddy\WordPress\MWC\Common\Extensions\Configuration\Contracts;

use GoDaddy\WordPress\MWC\Common\Extensions\Enums\BrandsEnum;

interface ManagedExtensionsRuntimeConfigurationContract
{
    /**
     * Gets a list of extension brands that should be excluded from the query results.
     *
     * @return array<BrandsEnum::*>
     */
    public function getExcludedBrands() : array;
}
