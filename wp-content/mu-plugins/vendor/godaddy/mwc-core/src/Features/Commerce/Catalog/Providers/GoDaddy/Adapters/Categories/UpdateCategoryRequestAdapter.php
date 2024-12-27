<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\Categories;

use Exception;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\Category;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\CategoryRequestInputs\UpdateCategoryInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Http\Requests\Request;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;

/**
 *  Request adapter for updating an existing Catalog Category.
 *
 * @method static UpdateCategoryRequestAdapter getNewInstance(UpdateCategoryInput $input)
 */
class UpdateCategoryRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanGetNewInstanceTrait;

    protected UpdateCategoryInput $input;

    /**
     * Constructor.
     *
     * @param UpdateCategoryInput $input
     */
    public function __construct(UpdateCategoryInput $input)
    {
        $this->input = $input;
    }

    /**
     * Converts the response into a {@see Category} instance.
     *
     * @param ResponseContract $response
     * @return Category
     */
    protected function convertResponse(ResponseContract $response) : Category
    {
        // @TODO update to use CategoryAdapter::convertToSourceFromArray() once CS9 is merged (no story yet) {agibson 2023-09-01}
        return $this->input->category;
    }

    /**
     * Converts the input object into a request object.
     *
     * @return Request
     * @throws Exception|CommerceException
     */
    public function convertFromSource() : RequestContract
    {
        if (! isset($this->input->category->categoryId) || empty($this->input->category->categoryId)) {
            throw new CommerceException('A category ID is required to build an update category request.');
        }

        $body = [
            'name'        => $this->input->category->name,
            'altId'       => $this->input->category->altId,
            'description' => $this->input->category->description,
            'parentId'    => $this->input->category->parentId,
        ];

        return Request::withAuth()
            ->setStoreId($this->input->storeId)
            ->setBody($body)
            ->setPath("/categories/{$this->input->category->categoryId}")
            ->setMethod('patch');
    }
}
