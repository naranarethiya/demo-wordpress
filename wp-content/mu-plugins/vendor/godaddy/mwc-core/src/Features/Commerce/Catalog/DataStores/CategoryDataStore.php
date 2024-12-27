<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataStores;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\WordPress\Adapters\TaxonomyTermAdapter;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Exceptions\CategoryAltIdNotUniqueException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Exceptions\CategoryNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\CategoryAltIdCollisionHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\CategoryEligibilityHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\CreateOrUpdateCategoryOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\CategoriesServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequestException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingCategoryRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Models\Category;
use WP_Term;

/**
 * Commerce Catalog categories data store.
 */
class CategoryDataStore
{
    /** @var CategoriesServiceContract */
    protected CategoriesServiceContract $categoriesService;

    /** @var CategoryAltIdCollisionHelper helper class to aid in  handling altId collision cases */
    protected CategoryAltIdCollisionHelper $altIdCollisionHelper;

    /**
     * Constructor.
     *
     * @param CategoriesServiceContract $categoriesService
     * @param CategoryAltIdCollisionHelper $altIdCollisionHelper
     */
    public function __construct(CategoriesServiceContract $categoriesService, CategoryAltIdCollisionHelper $altIdCollisionHelper)
    {
        $this->categoriesService = $categoriesService;
        $this->altIdCollisionHelper = $altIdCollisionHelper;
    }

    /**
     * Creates or updates a {@see Category} in the platform for the given local {@see WP_Term} category.
     *
     * @param WP_Term $category
     * @return void
     * @throws GatewayRequestException|CategoryNotFoundException|MissingCategoryRemoteIdException|CommerceExceptionContract
     */
    public function createOrUpdateCategoryInPlatform(WP_Term $category) : void
    {
        if (! $this->shouldWriteCategoryToPlatform($category)) {
            return;
        }

        $nativeCategory = TaxonomyTermAdapter::getNewInstance($category)->convertFromSource();
        $operation = CreateOrUpdateCategoryOperation::getNewInstance()->setCategory($nativeCategory);

        try {
            $this->categoriesService->createOrUpdateCategory($operation);
        } catch(CategoryAltIdNotUniqueException $e) {
            $this->altIdCollisionHelper->handle($nativeCategory);
        }
    }

    /**
     * Determines whether it should write a {@see WP_Term} category to the platform.
     *
     * @param WP_Term $category
     * @return bool
     */
    protected function shouldWriteCategoryToPlatform(WP_Term $category) : bool
    {
        return CategoryEligibilityHelper::shouldWriteCategoryToPlatform($category);
    }

    /**
     * Creates or updates a local product category as a {@see WP_Term}.
     *
     * All exceptions in this method are caught and reported to Sentry. If you need to be able to catch exceptions, call {@see static::createOrUpdateCategoryInPlatform()} directly.
     *
     * @param WP_Term $category
     * @return void
     */
    public function createOrUpdate(WP_Term $category) : void
    {
        try {
            $this->createOrUpdateCategoryInPlatform($category);
        } catch(Exception|CommerceExceptionContract $e) {
            SentryException::getNewInstance(sprintf('An error occurred trying to create or update a remote record for a category: %s', $e->getMessage()), $e);
        }
    }
}
