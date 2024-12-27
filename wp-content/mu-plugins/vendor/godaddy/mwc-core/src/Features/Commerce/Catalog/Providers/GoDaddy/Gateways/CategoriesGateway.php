<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Gateways;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Exceptions\CategoryAltIdNotUniqueException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\Contracts\CategoriesGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\Category;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\CategoryRequestInputs\CreateCategoryInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\CategoryRequestInputs\ListCategoriesInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\CategoryRequestInputs\ReadCategoryInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\CategoryRequestInputs\UpdateCategoryInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\Categories\CreateCategoryRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\Categories\ListCategoriesRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\Categories\ReadCategoryRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\Categories\UpdateCategoryRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\ListCategoriesResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequest404Exception;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingCategoryRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Gateways\AbstractGateway;

/**
 * GoDaddy categories gateway.
 */
class CategoriesGateway extends AbstractGateway implements CategoriesGatewayContract
{
    use CanGetNewInstanceTrait;

    /**
     * Creates a category.
     *
     * @param CreateCategoryInput $input
     * @return Category
     * @throws BaseException|CategoryAltIdNotUniqueException|CommerceExceptionContract|Exception
     */
    public function create(CreateCategoryInput $input) : Category
    {
        /** @var Category $result */
        $result = $this->doAdaptedRequest(CreateCategoryRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * Updates a category.
     *
     * @param UpdateCategoryInput $input
     * @return Category
     * @throws BaseException|CategoryAltIdNotUniqueException|CommerceExceptionContract|Exception
     */
    public function update(UpdateCategoryInput $input) : Category
    {
        /** @var Category $result */
        $result = $this->doAdaptedRequest(UpdateCategoryRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * Reads a category.
     *
     * @param ReadCategoryInput $input
     * @return Category
     * @throws BaseException|CommerceExceptionContract|MissingCategoryRemoteIdException|GatewayRequest404Exception|Exception
     */
    public function read(ReadCategoryInput $input) : Category
    {
        /** @var Category $result */
        $result = $this->doAdaptedRequest(ReadCategoryRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * Lists categories.
     *
     * @param ListCategoriesInput $input
     * @return ListCategoriesResponseContract
     * @throws BaseException|CommerceExceptionContract|Exception
     */
    public function list(ListCategoriesInput $input) : ListCategoriesResponseContract
    {
        /** @var ListCategoriesResponseContract $result */
        $result = $this->doAdaptedRequest(ListCategoriesRequestAdapter::getNewInstance($input));

        return $result;
    }
}
