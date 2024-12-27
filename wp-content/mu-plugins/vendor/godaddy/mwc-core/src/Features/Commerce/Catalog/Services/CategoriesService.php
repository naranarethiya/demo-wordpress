<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services;

use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Models\Taxonomy;
use GoDaddy\WordPress\MWC\Common\Models\Term;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Exceptions\CategoryMappingNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\CreateOrUpdateCategoryOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ListCategoriesOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ReadCategoryOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\CreateOrUpdateCategoryOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\Contracts\CatalogProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\Category;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\CategoryRequestInputs\CreateCategoryInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\CategoryRequestInputs\ListCategoriesInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\CategoryRequestInputs\ReadCategoryInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\CategoryRequestInputs\UpdateCategoryInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\CategoryAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\CategoriesCachingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\CategoriesMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\CategoriesServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\CreateOrUpdateCategoryResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\ListCategoriesResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\ReadCategoryResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\CreateOrUpdateCategoryResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\ReadCategoryResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequest404Exception;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequestException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingCategoryLocalIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingCategoryRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingCategoryRemoteIdForParentException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Exceptions\CachingStrategyException;

/**
 * Handles communication between Managed WooCommerce and the commerce catalog API for category CRUD operation.
 */
class CategoriesService implements CategoriesServiceContract
{
    /** @var CommerceContextContract context of the current site - contains the store ID */
    protected CommerceContextContract $commerceContext;

    /** @var CatalogProviderContract provider to the external API's CRUD operations */
    protected CatalogProviderContract $catalogProvider;

    /** @var CategoriesMappingServiceContract service that handles mapping local entities to their remote equivalents */
    protected CategoriesMappingServiceContract $categoriesMappingService;

    /** @var CategoriesCachingServiceContract service for caching remote category objects */
    protected CategoriesCachingServiceContract $categoriesCachingService;

    /** @var CategoryAdapter used to convert a local {@see Term} object into a {@see Category} object for the platform */
    protected CategoryAdapter $categoryAdapter;

    public function __construct(
        CommerceContextContract $commerceContext,
        CatalogProviderContract $catalogProvider,
        CategoriesMappingServiceContract $categoriesMappingService,
        CategoriesCachingServiceContract $categoriesCachingService,
        CategoryAdapter $categoryAdapter
    ) {
        $this->commerceContext = $commerceContext;
        $this->catalogProvider = $catalogProvider;
        $this->categoriesMappingService = $categoriesMappingService;
        $this->categoriesCachingService = $categoriesCachingService;
        $this->categoryAdapter = $categoryAdapter;
    }

    /**
     * Creates or updates a category.
     *
     * @param CreateOrUpdateCategoryOperationContract $operation
     * @return CreateOrUpdateCategoryResponseContract
     * @throws CommerceExceptionContract|CachingStrategyException|MissingCategoryLocalIdException|AdapterException
     */
    public function createOrUpdateCategory(CreateOrUpdateCategoryOperationContract $operation) : CreateOrUpdateCategoryResponseContract
    {
        $localId = $operation->getCategory()->getId();

        if (! $localId) {
            throw new MissingCategoryLocalIdException('Cannot create a category without a local ID.');
        }

        if ($remoteId = $this->categoriesMappingService->getRemoteId($operation->getCategory())) {
            return $this->updateCategory($operation, $remoteId);
        } else {
            return $this->createCategory($operation);
        }
    }

    /**
     * Creates or updates the category in the remote service.
     *
     * @param CreateOrUpdateCategoryOperationContract $operation
     * @return CreateOrUpdateCategoryResponseContract
     * @throws GatewayRequestException|CommerceExceptionContract|AdapterException|MissingCategoryRemoteIdForParentException
     */
    public function createCategory(CreateOrUpdateCategoryOperationContract $operation) : CreateOrUpdateCategoryResponseContract
    {
        $this->maybeHandleDependencies($operation);

        $category = $this->catalogProvider
            ->categories()
            ->create($this->getCreateCategoryInput($operation));

        if (empty($category->categoryId)) {
            throw MissingCategoryRemoteIdException::withDefaultMessage();
        }

        $this->categoriesMappingService->saveRemoteId($operation->getCategory(), $category->categoryId);

        return CreateOrUpdateCategoryResponse::getNewInstance($category->categoryId);
    }

    /**
     * Gets the {@see CreateCategoryInput} DTO for the provided operation.
     *
     * @param CreateOrUpdateCategoryOperationContract $operation
     * @return CreateCategoryInput
     * @throws AdapterException|MissingCategoryRemoteIdForParentException
     */
    protected function getCreateCategoryInput(CreateOrUpdateCategoryOperationContract $operation) : CreateCategoryInput
    {
        $category = $this->adaptCategoryForRemoteOperation($operation->getCategory());

        return CreateCategoryInput::getNewInstance([
            'category' => $category,
            'storeId'  => $this->commerceContext->getStoreId(),
        ]);
    }

    /**
     * Updates a category.
     *
     * @param CreateOrUpdateCategoryOperationContract $operation
     * @param string $remoteId
     * @return CreateOrUpdateCategoryResponseContract
     * @throws CommerceExceptionContract|CachingStrategyException|AdapterException|MissingCategoryRemoteIdForParentException
     */
    public function updateCategory(CreateOrUpdateCategoryOperationContract $operation, string $remoteId) : CreateOrUpdateCategoryResponseContract
    {
        $this->maybeHandleDependencies($operation);

        $category = $this->catalogProvider
            ->categories()
            ->update($this->getUpdateCategoryInput($operation, $remoteId));

        if (empty($category->categoryId)) {
            throw MissingCategoryRemoteIdException::withDefaultMessage();
        }

        $this->categoriesCachingService->remove($remoteId);

        return CreateOrUpdateCategoryResponse::getNewInstance($category->categoryId);
    }

    /**
     * Gets the {@see UpdateCategoryInput} DTO for the provided operation.
     *
     * @param CreateOrUpdateCategoryOperationContract $operation
     * @param string $remoteId
     * @return UpdateCategoryInput
     * @throws AdapterException|MissingCategoryRemoteIdForParentException
     */
    protected function getUpdateCategoryInput(CreateOrUpdateCategoryOperationContract $operation, string $remoteId) : UpdateCategoryInput
    {
        $category = $this->adaptCategoryForRemoteOperation($operation->getCategory());

        $category->categoryId = $remoteId;

        return UpdateCategoryInput::getNewInstance([
            'category' => $category,
            'storeId'  => $this->commerceContext->getStoreId(),
        ]);
    }

    /**
     * Converts a local {@see Term} object into a remote {@see Category} DTO.
     *
     * @param Term $localCategory
     * @return Category
     * @throws AdapterException|MissingCategoryRemoteIdForParentException
     */
    protected function adaptCategoryForRemoteOperation(Term $localCategory) : Category
    {
        return $this->categoryAdapter->convertToSource($localCategory);
    }

    /**
     * Handles creating any dependencies, before we can update/create a category in the platform.
     *
     *  This ensures any parent categories exist.
     *
     * @param CreateOrUpdateCategoryOperationContract $operation
     * @return void
     * @throws AdapterException|CommerceException|CommerceExceptionContract|GatewayRequestException|MissingCategoryRemoteIdException
     */
    protected function maybeHandleDependencies(CreateOrUpdateCategoryOperationContract $operation) : void
    {
        if (! $localParentId = $operation->getCategory()->getParentId()) {
            return;
        }

        /** @var Term $localParentTerm */
        $localParentTerm = CatalogIntegration::withoutReads(fn () => Term::get($localParentId));

        $remoteParentId = $this->categoriesMappingService->getRemoteId($localParentTerm);

        if ($remoteParentId) {
            // already exists in the service -- we're good!
            return;
        }

        $this->createCategory(CreateOrUpdateCategoryOperation::getNewInstance()->setCategory($localParentTerm));
    }

    /**
     * Reads a category from the remote service by the local ID.
     *
     * @param ReadCategoryOperationContract $operation
     * @return ReadCategoryResponseContract
     * @throws CategoryMappingNotFoundException|CommerceExceptionContract|MissingCategoryRemoteIdException|GatewayRequest404Exception
     */
    public function readCategory(ReadCategoryOperationContract $operation) : ReadCategoryResponseContract
    {
        $remoteId = $this->getRemoteIdFromLocalId($operation->getLocalId());

        if (! $remoteId) {
            throw new CategoryMappingNotFoundException('No local mapping found for category.');
        }

        $category = $this->categoriesCachingService->remember(
            $remoteId,
            fn () => $this->catalogProvider->categories()->read($this->getReadCategoryInput($remoteId))
        );

        return ReadCategoryResponse::getNewInstance($category);
    }

    /**
     * Gets the remote category ID for the local category ID.
     *
     * @param int $localId
     * @return string|null
     */
    protected function getRemoteIdFromLocalId(int $localId) : ?string
    {
        $taxonomy = Taxonomy::getNewInstance()->setName(CatalogIntegration::PRODUCT_CATEGORY_TAXONOMY);
        $term = Term::getNewInstance($taxonomy)->setId($localId);

        return $this->categoriesMappingService->getRemoteId($term);
    }

    /**
     * Gets the {@see ReadCategoryInput} DTO for the provided operation.
     *
     * @param string $remoteId
     * @return ReadCategoryInput
     */
    protected function getReadCategoryInput(string $remoteId) : ReadCategoryInput
    {
        return ReadCategoryInput::getNewInstance([
            'categoryId' => $remoteId,
            'storeId'    => $this->commerceContext->getStoreId(),
        ]);
    }

    /**
     * Lists categories.
     *
     * @param ListCategoriesOperationContract $operation
     * @return ListCategoriesResponseContract
     */
    public function listCategories(ListCategoriesOperationContract $operation) : ListCategoriesResponseContract
    {
        return $this->catalogProvider->categories()->list($this->getListCategoriesInput($operation));
    }

    /**
     * Gets the list categories input.
     *
     * @param ListCategoriesOperationContract $operation
     * @return ListCategoriesInput
     */
    protected function getListCategoriesInput(ListCategoriesOperationContract $operation) : ListCategoriesInput
    {
        return new ListCategoriesInput([
            'queryArgs' => $operation->toArray(),
            'storeId'   => $this->commerceContext->getStoreId(),
        ]);
    }
}
