<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\Categories;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Exceptions\CategoryAltIdNotUniqueException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\Category;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\CategoryRequestInputs\CreateCategoryInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Http\Requests\Request;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingCategoryRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;

/**
 * Request adapter for creating a new Catalog Category.
 *
 * @method static static getNewInstance(CreateCategoryInput $input)
 */
class CreateCategoryRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanGetNewInstanceTrait;

    /** @var CreateCategoryInput */
    protected CreateCategoryInput $input;

    /**
     * Constructor.
     *
     * @param CreateCategoryInput $input
     */
    public function __construct(CreateCategoryInput $input)
    {
        $this->input = $input;
    }

    /**
     * Converts the response into a new {@see Category} instance.
     *
     * @param ResponseContract $response
     * @return Category
     * @throws MissingCategoryRemoteIdException
     */
    protected function convertResponse(ResponseContract $response) : Category
    {
        $categoryId = TypeHelper::string(ArrayHelper::get((array) $response->getBody(), 'category.categoryId'), '');

        if (empty($categoryId)) {
            throw new MissingCategoryRemoteIdException('The category ID was not returned from the response.');
        }

        $this->input->category->categoryId = $categoryId;

        return $this->input->category;
    }

    /**
     * Converts the input object into a request object.
     *
     * @return RequestContract
     */
    public function convertFromSource() : RequestContract
    {
        $body = [
            'name'        => $this->input->category->name,
            'altId'       => $this->input->category->altId,
            'description' => $this->input->category->description,
            'depth'       => $this->input->category->depth,
            'parentId'    => $this->input->category->parentId,
        ];

        return Request::withAuth()
            ->setStoreId($this->input->storeId)
            ->setBody($body)
            ->setPath('/categories')
            ->setMethod('post');
    }

    /**
     * Throws an exception on error responses.
     *
     * @param ResponseContract $response
     * @return void
     * @throws CategoryAltIdNotUniqueException|CommerceExceptionContract
     */
    protected function throwIfIsErrorResponse(ResponseContract $response) : void
    {
        if ($this->isNotUniqueErrorResponse($response)) {
            throw CategoryAltIdNotUniqueException::getNewInstance($response->getErrorMessage() ?: 'A category with the same altId is already registered.');
        }

        parent::throwIfIsErrorResponse($response);
    }

    /**
     * Determines if the response represents a 409 error for a non-unique altId.
     *
     * @param ResponseContract $response
     * @return bool
     */
    protected function isNotUniqueErrorResponse(ResponseContract $response) : bool
    {
        return $response->isError()
            && 409 === $response->getStatus()
            && 'NOT_UNIQUE_ASSOCIATION_ALT_ID' === strtoupper(TypeHelper::string(ArrayHelper::get($response->getBody() ?: [], 'code'), ''));
    }
}
