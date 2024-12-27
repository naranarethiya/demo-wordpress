<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\CachingServiceContract;

/**
 * Contract for services to handle caching remote {@see ProductBase} objects.
 *
 * @method ProductBase remember(string $resourceIdentifier, callable $loader)
 * @method ProductBase|null get(string $resourceIdentifier)
 * @method ProductBase[] getMany(array $resourceIdentifiers)
 * @method set(ProductBase $resource)
 * @method setMany(ProductBase[] $resources)
 */
interface ProductsCachingServiceContract extends CachingServiceContract
{
}
