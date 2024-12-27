<?php

namespace GoDaddy\WordPress\MWC\Common\Extensions\Configuration;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Extensions\Configuration\Contracts\ManagedExtensionsRuntimeConfigurationContract;
use GoDaddy\WordPress\MWC\Common\Extensions\Enums\BrandsEnum;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;

class ManagedExtensionsRuntimeConfiguration implements ManagedExtensionsRuntimeConfigurationContract
{
    /**
     * {@inheritDoc}
     */
    public function getExcludedBrands() : array
    {
        return array_filter(array_map(
            static fn ($brand) => BrandsEnum::tryFrom(TypeHelper::string($brand, '')),
            TypeHelper::array(Configuration::get('mwc.extensions.api.excludedBrands'), [])
        ));
    }
}
