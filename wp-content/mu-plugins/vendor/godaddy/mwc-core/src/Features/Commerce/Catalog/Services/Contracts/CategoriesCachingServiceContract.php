<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\Category;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\CachingServiceContract;

/**
 * Contract for services to handle caching remote {@see Category} objects.
 *
 * @method Category remember(string $resourceIdentifier, callable $loader)
 * @method Category|null get(string $resourceIdentifier)
 * @method Category[] getMany(array $resourceIdentifiers)
 * @method set(Category $resource)
 * @method setMany(Category[] $resources)
 */
interface CategoriesCachingServiceContract extends CachingServiceContract
{
}
