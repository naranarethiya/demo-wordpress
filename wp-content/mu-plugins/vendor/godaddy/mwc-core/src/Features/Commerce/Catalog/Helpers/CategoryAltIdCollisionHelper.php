<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers;

use GoDaddy\WordPress\MWC\Common\Models\Term;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Exceptions\CategoryNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\ListCategoriesOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\Category;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\CategoriesMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\CategoriesServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequest404Exception;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingCategoryRemoteIdException;

/**
 * Handles {@see CategoryAltIdCollisionHelper} exceptions, as per this scenario:.
 *
 * - Category exists locally with slug `shirts`.
 * - This local Woo category has not yet been written to the platform.
 * - The platform already has a separate, existing category with altId `shirts`.
 * - We attempt to write the Woo category to the platform.
 * - We get an error back saying a category already exists with that altId.
 * - We now want to associate that local category with that pre-existing remote UUID (via our mapping table).
 */
class CategoryAltIdCollisionHelper
{
    /** @var CategoriesServiceContract */
    protected CategoriesServiceContract $categoriesService;

    /** @var CategoriesMappingServiceContract service that handles mapping local entities to their remote equivalents */
    protected CategoriesMappingServiceContract $categoriesMappingService;

    /**
     * Constructor.
     *
     * @param CategoriesServiceContract $categoriesService
     * @param CategoriesMappingServiceContract $categoriesMappingService
     */
    public function __construct(CategoriesServiceContract $categoriesService, CategoriesMappingServiceContract $categoriesMappingService)
    {
        $this->categoriesService = $categoriesService;
        $this->categoriesMappingService = $categoriesMappingService;
    }

    /**
     * Locates the upstream category with a matching altId and saves the category association in the mapping table.
     *
     * @param Term $nativeCategory
     * @return Category
     * @throws CategoryNotFoundException|MissingCategoryRemoteIdException|CommerceExceptionContract
     */
    public function handle(Term $nativeCategory) : Category
    {
        $remoteCategory = $this->getRemoteCategoryByAltId($nativeCategory->getName());

        if (empty($remoteCategory->categoryId)) {
            throw MissingCategoryRemoteIdException::withDefaultMessage();
        }

        $this->categoriesMappingService->saveRemoteId($nativeCategory, $remoteCategory->categoryId);

        return $remoteCategory;
    }

    /**
     * Gets the category from the platform that has the provided `altId`.
     *
     * @param string $altId
     * @return Category
     * @throws CategoryNotFoundException
     */
    protected function getRemoteCategoryByAltId(string $altId) : Category
    {
        try {
            $response = $this->categoriesService
                ->listCategories($this->getListOperation($altId));
        } catch(GatewayRequest404Exception $e) {
            // a 404 exception is thrown if there are no results for the query (no category with that altId)
            throw new CategoryNotFoundException("No category with altId {$altId} found.");
        }

        if (empty($response->getCategories()[0])) {
            throw new CategoryNotFoundException("No category with altId {$altId} found.");
        }

        $firstCategory = $response->getCategories()[0];

        // Sanity check to confirm altId actually matches.
        if ($firstCategory->altId !== $altId) {
            throw new CategoryNotFoundException("Found category does not have altId {$altId}.");
        }

        return $firstCategory;
    }

    /**
     * Gets the operation for our list request.
     *
     * @param string $altId
     * @return ListCategoriesOperation
     */
    protected function getListOperation(string $altId) : ListCategoriesOperation
    {
        return ListCategoriesOperation::getNewInstance()->setAltId($altId);
    }
}
