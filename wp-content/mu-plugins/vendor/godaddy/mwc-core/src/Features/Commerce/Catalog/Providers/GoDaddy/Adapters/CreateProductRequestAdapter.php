<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductRequestInputs\CreateProductInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\Traits\CanConvertProductResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Http\Requests\Request;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\NotUniqueException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;

/**
 * Product creation request adapter.
 *
 * @method static static getNewInstance(CreateProductInput $input)
 */
class CreateProductRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanGetNewInstanceTrait;
    use CanConvertProductResponseTrait;

    /** @var CreateProductInput data used to build the request */
    protected CreateProductInput $input;

    /**
     * Constructor.
     *
     * @param CreateProductInput $input
     */
    public function __construct(CreateProductInput $input)
    {
        $this->input = $input;
    }

    /**
     * {@inheritDoc}
     */
    public function convertFromSource() : RequestContract
    {
        $body = $this->input->product->toArray();

        // Set channelIds for the POST request.
        $body['channelIds'] = $this->input->channelIds->add;

        /*
         * We do not need certain properties in the request body:
         *
         * - we should not have a remote `productId` value when _creating_ a product;
         * - we cannot set dates when writing to the catalog service;
         * - null `salePrice` isn't allowed, it needs to be fully omitted when not present;
         * - `variants` is a readonly property and should not be written
         *
         * @TODO this logic will be refactored and improved in MWC-12385, as a hard-coded list like this is not sustainable {unfulvio 2023-06-07}
         * @see UpdateProductRequestAdapter::convertFromSource() for a similar case
         */
        unset($body['productId'], $body['createdAt'], $body['updatedAt'], $body['variants']);

        if (empty($body['salePrice'])) {
            unset($body['salePrice']);
        }

        return Request::withAuth()
            ->setStoreId($this->input->storeId)
            ->setBody($body)
            ->setPath('/products')
            ->setMethod('post');
    }

    /**
     * Converts gateway response to source.
     *
     * @param ResponseContract $response
     * @return ProductBase
     * @throws MissingProductRemoteIdException
     */
    public function convertResponse(ResponseContract $response) : ProductBase
    {
        return $this->convertProductResponse(
            TypeHelper::array(ArrayHelper::get((array) $response->getBody(), 'product'), [])
        );
    }

    /**
     * Throws exceptions on error responses.
     *
     * @param ResponseContract $response
     * @return void
     * @throws NotUniqueException|CommerceExceptionContract
     */
    protected function throwIfIsErrorResponse(ResponseContract $response) : void
    {
        if ($response->isError() && 409 === $response->getStatus() && 'NOT_UNIQUE_ERROR' === strtoupper(TypeHelper::string(ArrayHelper::get($response->getBody(), 'code'), ''))) {
            throw NotUniqueException::getNewInstance($response->getErrorMessage() ?: 'Record is not unique.');
        }

        parent::throwIfIsErrorResponse($response);
    }
}
