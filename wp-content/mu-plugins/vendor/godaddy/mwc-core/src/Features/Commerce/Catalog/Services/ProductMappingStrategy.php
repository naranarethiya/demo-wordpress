<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsMappingStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractMappingStrategy;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Traits\CanMapNumericIdentifierToRemoteIdsTrait;

/**
 * Mapping strategy for products. This associates a numeric local ID with a string remote ID.
 */
class ProductMappingStrategy extends AbstractMappingStrategy implements ProductsMappingStrategyContract
{
    use CanMapNumericIdentifierToRemoteIdsTrait;
}
