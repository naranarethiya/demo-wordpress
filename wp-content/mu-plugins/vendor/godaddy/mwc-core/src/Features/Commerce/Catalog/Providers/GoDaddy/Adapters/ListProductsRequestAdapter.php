<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductRequestInputs\ListProductsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\Traits\CanConvertProductResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\Traits\CanConvertQueryArgsFromSourceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Http\Requests\Request;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;

/**
 * Adapter to convert a Commerce list products response to an array of {@see ProductBase} object.
 *
 * @method static static getNewInstance(ListProductsInput $input)
 */
class ListProductsRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanGetNewInstanceTrait;
    use CanConvertProductResponseTrait;
    use CanConvertQueryArgsFromSourceTrait;

    protected ListProductsInput $input;

    /**
     * Constructor.
     *
     * @param ListProductsInput $input
     */
    public function __construct(ListProductsInput $input)
    {
        $this->input = $input;
    }

    /**
     * Converts a list products response into an array of {@see ProductBase} objects.
     *
     * @param ResponseContract $response
     * @return ProductBase[]
     * @throws MissingProductRemoteIdException
     */
    protected function convertResponse(ResponseContract $response) : array
    {
        return array_map(function ($data) {
            return $this->convertProductResponse(TypeHelper::array($data, []));
        }, ArrayHelper::wrap(ArrayHelper::get(ArrayHelper::wrap($response->getBody()), 'products', [])));
    }

    /**
     * Converts the source list products input to a gateway request.
     *
     * @return RequestContract
     */
    public function convertFromSource() : RequestContract
    {
        $request = Request::withAuth()
            ->setStoreId($this->input->storeId)
            ->setPath('/products');

        $request->setQuery($this->convertQueryArgsFromSource());

        return $request;
    }
}
