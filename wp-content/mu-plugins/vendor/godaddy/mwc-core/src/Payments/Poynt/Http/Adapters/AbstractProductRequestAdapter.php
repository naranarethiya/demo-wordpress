<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\AbstractProductRequest;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * An adapter for converting the core product object to and from a Poynt API product request.
 */
abstract class AbstractProductRequestAdapter implements DataSourceAdapterContract
{
    /** @var Product|null */
    protected $source;

    /**
     * Constructs the adapter.
     *
     * @param Product $product
     */
    public function __construct(Product $product)
    {
        $this->source = $product;
    }

    /**
     * Converts data from Poynt API product request to the source Product object.
     *
     * @param Response|null $response
     * @return Product
     */
    public function convertToSource(Response $response = null) : Product
    {
        return $this->getProductAdapter()->convertToSource($response && $response->getBody() ? $response->getBody() : []);
    }

    /**
     * Gets the product adapter instance.
     *
     * @return ProductAdapter
     */
    public function getProductAdapter() : ProductAdapter
    {
        return new ProductAdapter($this->source);
    }

    /**
     * Gets the product request instance.
     *
     * @throws Exception
     */
    abstract protected function getProductRequest() : AbstractProductRequest;
}
