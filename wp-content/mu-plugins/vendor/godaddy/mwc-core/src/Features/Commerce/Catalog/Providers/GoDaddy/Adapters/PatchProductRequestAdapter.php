<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductRequestInputs\PatchProductInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\Traits\CanConvertProductResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\Traits\CanThrowIfIsProductNotFoundErrorResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Http\Requests\Request;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;

/**
 * Adapter to convert patch product requests and responses.
 *
 * @method static static getNewInstance(PatchProductInput $input)
 */
class PatchProductRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanGetNewInstanceTrait;
    use CanConvertProductResponseTrait;
    use CanThrowIfIsProductNotFoundErrorResponseTrait;

    protected PatchProductInput $input;

    public function __construct(PatchProductInput $input)
    {
        $this->input = $input;
    }

    /**
     * Converts a Commerce product response to a {@see ProductBase} object.
     *
     * @param ResponseContract $response
     * @return ProductBase
     * @throws MissingProductRemoteIdException
     */
    protected function convertResponse(ResponseContract $response) : ProductBase
    {
        $responseData = TypeHelper::array(ArrayHelper::get($response->getBody() ?: [], 'product'), []);

        return $this->convertProductResponse($responseData);
    }

    /**
     * {@inheritDoc}
     */
    public function convertFromSource() : RequestContract
    {
        return Request::withAuth()
            ->setStoreId($this->input->storeId)
            ->setBody($this->input->properties)
            ->setPath("/products/{$this->input->productId}")
            ->setMethod('patch');
    }
}
