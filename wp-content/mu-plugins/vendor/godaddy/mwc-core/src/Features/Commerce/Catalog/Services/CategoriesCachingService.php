<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\Category;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\CategoryAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\CategoriesCachingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Factories\CategoriesCachingStrategyFactory;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceResourceTypes;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingCategoryRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractCachingService;

/**
 * Commerce Product Categories caching service.
 */
class CategoriesCachingService extends AbstractCachingService implements CategoriesCachingServiceContract
{
    /** @var CategoryAdapter */
    protected CategoryAdapter $categoryAdapter;

    /** @var string */
    protected string $resourceType = CommerceResourceTypes::ProductCategory;

    /** @var int cache TTL (in seconds) for entries */
    protected int $cacheTtl = 86400; // 24 hours

    /**
     * Constructor.
     *
     * @param CategoriesCachingStrategyFactory $cachingStrategy
     * @param CategoryAdapter $categoryAdapter
     */
    public function __construct(CategoriesCachingStrategyFactory $cachingStrategy, CategoryAdapter $categoryAdapter)
    {
        $this->categoryAdapter = $categoryAdapter;

        parent::__construct($cachingStrategy);
    }

    /**
     * Converts the array of category data to a {@see Category} DTO.
     *
     * @param array<string, mixed> $resourceArray
     * @return Category
     * @throws MissingCategoryRemoteIdException
     */
    protected function makeResourceFromArray(array $resourceArray) : object
    {
        return $this->categoryAdapter->convertToSourceFromArray($resourceArray);
    }

    /**
     * Gets the remote ID of the given resource.
     *
     * @param Category&object $resource
     * @return string
     * @throws MissingCategoryRemoteIdException
     */
    protected function getResourceIdentifier(object $resource) : string
    {
        if (! empty($resource->categoryId)) {
            return $resource->categoryId;
        }

        throw MissingCategoryRemoteIdException::withDefaultMessage();
    }
}
