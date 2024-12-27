<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services;

use Exception;
use GoDaddy\WordPress\MWC\Common\Providers\Jitter\Contracts\PercentageJitterProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Level;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Traits\CanConvertLevelResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\CachingStrategyFactoryContract;

/**
 * @method Level|null get(string $resourceIdentifier)
 * @method Level[] getMany(array $resourceIdentifiers)
 * @method Level remember(string $resourceIdentifier, callable $loader)
 * @method set(Level $resource)
 * @method setMany(Level[] $resources)
 */
class LevelsCachingService extends AbstractCachingService
{
    use CanConvertLevelResponseTrait;

    protected string $resourceType = 'inventory-levels-by-product-id';

    public function __construct(
        CachingStrategyFactoryContract $cachingStrategyFactory,
        PercentageJitterProviderContract $jitterProvider
    ) {
        parent::__construct($cachingStrategyFactory);

        $this->jitterProvider = $jitterProvider;
    }

    /**
     * {@inheritDoc}
     *
     * @return Level
     * @throws Exception
     */
    protected function makeResourceFromArray(array $resourceArray) : Level
    {
        return $this->convertLevelResponse($resourceArray);
    }

    /**
     * {@inheritDoc}
     *
     * @param Level $resource
     *
     * @throws MissingProductRemoteIdException
     */
    protected function getResourceIdentifier(object $resource) : string
    {
        if (! empty($resource->productId)) {
            return $resource->productId;
        }

        throw MissingProductRemoteIdException::withDefaultMessage();
    }
}
