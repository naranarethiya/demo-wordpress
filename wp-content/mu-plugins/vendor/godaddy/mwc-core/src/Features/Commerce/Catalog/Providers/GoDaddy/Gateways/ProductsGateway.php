<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Gateways;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\Contracts\ProductsGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductRequestInputs\CreateProductInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductRequestInputs\ListProductsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductRequestInputs\PatchProductInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductRequestInputs\ReadProductInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductRequestInputs\UpdateProductInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\CreateProductRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\ListProductsRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\PatchProductRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\ReadProductRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\UpdateProductRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Gateways\AbstractGateway;

/**
 * GoDaddy products gateway.
 */
class ProductsGateway extends AbstractGateway implements ProductsGatewayContract
{
    use CanGetNewInstanceTrait;

    /**
     * Creates a product.
     *
     * @param CreateProductInput $input
     * @return ProductBase
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    public function create(CreateProductInput $input) : ProductBase
    {
        /** @var ProductBase $result */
        $result = $this->doAdaptedRequest(CreateProductRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * Updates a product.
     *
     * @param UpdateProductInput $input
     * @return ProductBase
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    public function update(UpdateProductInput $input) : ProductBase
    {
        /** @var ProductBase $result */
        $result = $this->doAdaptedRequest(UpdateProductRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * Reads a product.
     *
     * @param ReadProductInput $input
     * @return ProductBase
     * @throws BaseException|CommerceExceptionContract|Exception
     */
    public function read(ReadProductInput $input) : ProductBase
    {
        /** @var ProductBase $result */
        $result = $this->doAdaptedRequest(ReadProductRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * Lists products.
     *
     * @param ListProductsInput $input
     * @return ProductBase[]
     * @throws BaseException|CommerceExceptionContract|Exception
     */
    public function list(ListProductsInput $input) : array
    {
        /** @var ProductBase[] $result */
        $result = $this->doAdaptedRequest(ListProductsRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * Patches a product.
     *
     * @param PatchProductInput $input
     * @return ProductBase
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    public function patch(PatchProductInput $input) : ProductBase
    {
        /** @var ProductBase $result */
        $result = $this->doAdaptedRequest(PatchProductRequestAdapter::getNewInstance($input));

        return $result;
    }
}
