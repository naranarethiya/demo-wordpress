<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Exceptions\CategoryMappingNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\CreateOrUpdateCategoryOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ListCategoriesOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ReadCategoryOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\CreateOrUpdateCategoryResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\ListCategoriesResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\ReadCategoryResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequest404Exception;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequestException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingCategoryRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Exceptions\CachingStrategyException;

/**
 * Contract for categories services.
 */
interface CategoriesServiceContract
{
    /**
     * Creates or updates a category.
     *
     * @param CreateOrUpdateCategoryOperationContract $operation
     * @return CreateOrUpdateCategoryResponseContract
     * @throws GatewayRequestException
     */
    public function createOrUpdateCategory(CreateOrUpdateCategoryOperationContract $operation) : CreateOrUpdateCategoryResponseContract;

    /**
     * Creates a category.
     *
     * @param CreateOrUpdateCategoryOperationContract $operation
     * @return CreateOrUpdateCategoryResponseContract
     * @throws GatewayRequestException
     */
    public function createCategory(CreateOrUpdateCategoryOperationContract $operation) : CreateOrUpdateCategoryResponseContract;

    /**
     * Updates a category.
     *
     * @param CreateOrUpdateCategoryOperationContract $operation
     * @param string $remoteId
     * @return CreateOrUpdateCategoryResponseContract
     * @throws GatewayRequestException
     */
    public function updateCategory(CreateOrUpdateCategoryOperationContract $operation, string $remoteId) : CreateOrUpdateCategoryResponseContract;

    /**
     * Reads a category.
     *
     * @param ReadCategoryOperationContract $operation
     * @return ReadCategoryResponseContract
     * @throws CategoryMappingNotFoundException|CommerceExceptionContract|MissingCategoryRemoteIdException|GatewayRequest404Exception|CachingStrategyException
     */
    public function readCategory(ReadCategoryOperationContract $operation) : ReadCategoryResponseContract;

    /**
     * Lists categories.
     *
     * @param ListCategoriesOperationContract $operation
     * @return ListCategoriesResponseContract
     * @throws GatewayRequest404Exception
     */
    public function listCategories(ListCategoriesOperationContract $operation) : ListCategoriesResponseContract;
}
