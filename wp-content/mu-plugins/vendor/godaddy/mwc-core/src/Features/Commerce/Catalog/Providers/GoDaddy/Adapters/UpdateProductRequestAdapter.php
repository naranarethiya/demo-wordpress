<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductRequestInputs\UpdateProductInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\Traits\CanConvertProductResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Http\Requests\Request;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;

/**
 * Product update request adapter.
 *
 * @method static static getNewInstance(UpdateProductInput $input)
 */
class UpdateProductRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanGetNewInstanceTrait;
    use CanConvertProductResponseTrait;

    /** @var UpdateProductInput data used to create the request */
    protected UpdateProductInput $input;

    /**
     * Constructor.
     *
     * @param UpdateProductInput $input
     */
    public function __construct(UpdateProductInput $input)
    {
        $this->input = $input;
    }

    /**
     * {@inheritDoc}
     */
    public function convertFromSource() : RequestContract
    {
        if (! isset($this->input->product->productId) || empty($this->input->product->productId)) {
            throw new CommerceException('A product ID is required to build an update product request.');
        }

        $body = $this->input->product->toArray();

        // Set channelIds for the PATCH request.
        $body['channelIds'] = $this->input->channelIds->toArray();

        /*
         * We do not need certain properties in the request body:
         *
         * - the productId is only needed to build the API path;
         * - the API does not support updates using the parentId parameter;
         * - we do not implement `altId` and do not want to overwrite values other channels may have set;
         * - we are not supposed to set `updatedAt` or `createdAt` when writing to the catalog service;
         * - `variants` is a readonly property and should not be written;
         *
         * @TODO this logic will be refactored and improved in MWC-12385, as a hard-coded list like this is not sustainable {agibson 2023-05-24}
         * @see CreateProductRequestAdapter::convertFromSource() for a similar case
         */
        unset($body['productId'], $body['parentId'], $body['altId'], $body['updatedAt'], $body['createdAt'], $body['variants']);

        return Request::withAuth()
            ->setStoreId($this->input->storeId)
            ->setBody($body)
            ->setPath("/products/{$this->input->product->productId}")
            ->setMethod('patch');
    }

    /**
     * Converts gateway response to source.
     *
     * @param ResponseContract $response
     * @return ProductBase
     * @throws MissingProductRemoteIdException
     */
    protected function convertResponse(ResponseContract $response) : ProductBase
    {
        return $this->convertProductResponse(
            TypeHelper::array(ArrayHelper::get((array) $response->getBody(), 'product'), [])
        );
    }
}
