<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductRequestInputs\ReadProductInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\Traits\CanConvertProductResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\Traits\CanThrowIfIsProductNotFoundErrorResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Http\Requests\Request;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;

/**
 * Adapter to convert a Commerce product read response to a {@see ProductBase} object.
 *
 * @method static static getNewInstance(ReadProductInput $input)
 */
class ReadProductRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanThrowIfIsProductNotFoundErrorResponseTrait;
    use CanGetNewInstanceTrait;
    use CanConvertProductResponseTrait;

    /** @var ReadProductInput */
    protected ReadProductInput $input;

    /**
     * Constructor.
     *
     * @param ReadProductInput $input
     */
    public function __construct(ReadProductInput $input)
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
    public function convertResponse(ResponseContract $response) : ProductBase
    {
        $responseData = TypeHelper::array(ArrayHelper::get($response->getBody() ?: [], 'product'), []);

        return $this->convertProductResponse($responseData);
    }

    /**
     * Converts the source product read input to a gateway request.
     *
     * @return RequestContract
     */
    public function convertFromSource() : RequestContract
    {
        return Request::withAuth()
            ->setStoreId($this->input->storeId)
            ->setPath("/products/{$this->input->productId}");
    }
}
