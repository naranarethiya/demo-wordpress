<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\Categories;

use GoDaddy\WordPress\MWC\Common\Container\ContainerFactory;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\ContainerException;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\EntryNotFoundException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\Category;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\CategoryRequestInputs\ListCategoriesInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\CategoryAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\Traits\CanConvertQueryArgsFromSourceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Http\Requests\Request;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\ListCategoriesResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingCategoryRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;

/**
 * Adapter to convert a Commerce list categories response to a {@see Category} object.
 *
 * @method static static getNewInstance(ListCategoriesInput $input)
 */
class ListCategoriesRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanGetNewInstanceTrait;
    use CanConvertQueryArgsFromSourceTrait;

    /** @var ListCategoriesInput */
    protected ListCategoriesInput $input;

    /** @var CategoryAdapter */
    protected CategoryAdapter $categoryAdapter;

    /**
     * Constructor.
     *
     * @param ListCategoriesInput $input
     * @throws ContainerException|EntryNotFoundException
     */
    public function __construct(ListCategoriesInput $input)
    {
        $this->input = $input;

        /** @var CategoryAdapter $categoryAdapter */
        $categoryAdapter = ContainerFactory::getInstance()->getSharedContainer()->get(CategoryAdapter::class);

        $this->categoryAdapter = $categoryAdapter;
    }

    /**
     * Converts the response to a {@see ListCategoriesResponse} instance.
     *
     * @param ResponseContract $response
     * @return ListCategoriesResponse
     */
    protected function convertResponse(ResponseContract $response) : ListCategoriesResponse
    {
        $categories = array_map(function ($data) {
            return $this->convertCategoryResponse(TypeHelper::array($data, []));
        }, ArrayHelper::wrap(ArrayHelper::get(ArrayHelper::wrap($response->getBody()), 'categories', [])));

        return ListCategoriesResponse::getNewInstance($categories);
    }

    /**
     * Converts an individual category from response data to a {@see Category} instance.
     *
     * @param array<string, mixed> $categoryData
     * @return Category
     * @throws MissingCategoryRemoteIdException
     */
    protected function convertCategoryResponse(array $categoryData) : Category
    {
        return $this->categoryAdapter->convertToSourceFromArray($categoryData);
    }

    /**
     * Converts the request to the source format.
     *
     * @return RequestContract
     */
    public function convertFromSource() : RequestContract
    {
        return Request::withAuth()
            ->setStoreId($this->input->storeId)
            ->setPath('/categories')
            ->setQuery($this->convertQueryArgsFromSource());
    }
}
