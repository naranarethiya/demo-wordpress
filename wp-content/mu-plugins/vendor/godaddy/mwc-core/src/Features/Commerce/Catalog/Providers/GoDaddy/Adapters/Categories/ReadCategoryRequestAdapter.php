<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\Categories;

use GoDaddy\WordPress\MWC\Common\Container\ContainerFactory;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\ContainerException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\Category;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\CategoryRequestInputs\ReadCategoryInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\CategoryAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Http\Requests\Request;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingCategoryRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;

/**
 * Adapter to convert a Commerce category read response to a {@see Category} object.
 *
 * @method static static getNewInstance(ReadCategoryInput $input)
 */
class ReadCategoryRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanGetNewInstanceTrait;

    protected ReadCategoryInput $input;

    /**
     * Constructor.
     *
     * @param ReadCategoryInput $input
     */
    public function __construct(ReadCategoryInput $input)
    {
        $this->input = $input;
    }

    /**
     * Commerce category read response array data to a {@see Category} object.
     *
     * @param ResponseContract $response
     * @return Category
     * @throws ContainerException|MissingCategoryRemoteIdException
     */
    protected function convertResponse(ResponseContract $response) : Category
    {
        $responseData = TypeHelper::array(ArrayHelper::get($response->getBody() ?: [], 'result'), []);

        /** @var CategoryAdapter $categoryAdapter */
        $categoryAdapter = ContainerFactory::getInstance()->getSharedContainer()->get(CategoryAdapter::class);

        return $categoryAdapter->convertToSourceFromArray($responseData);
    }

    /**
     * {@inheritDoc}
     */
    public function convertFromSource() : RequestContract
    {
        return Request::withAuth()
            ->setStoreId($this->input->storeId)
            ->setPath("/categories/{$this->input->categoryId}");
    }
}
