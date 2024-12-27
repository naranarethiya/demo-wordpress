<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\CategoriesMappingStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractMappingStrategy;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Traits\CanMapNumericIdentifierToRemoteIdsTrait;

/**
 * Mapping strategy for product categories. This associates a numeric local ID with a string remote ID.
 */
class CategoriesMappingStrategy extends AbstractMappingStrategy implements CategoriesMappingStrategyContract
{
    use CanMapNumericIdentifierToRemoteIdsTrait;
}
