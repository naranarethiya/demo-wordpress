<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways\ProductsGateway;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\AbstractProductRequest;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\AbstractResourceRequest;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\ProductListRequest;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * An adapter for converting the core product gateway to and from Poynt API data.
 */
class ProductListRequestAdapter extends AbstractListRequestAdapter
{
    /**
     * Constructs the adapter.
     *
     * @param ProductsGateway $gateway
     */
    public function __construct(ProductsGateway $gateway)
    {
        $this->source = $gateway;
        $this->responseBodyKey = AbstractProductRequest::RESOURCE_PLURAL;
    }

    protected function getRequest() : AbstractResourceRequest
    {
        return new ProductListRequest();
    }

    /**
     * Returns the adapter for converting the Product object to and from Poynt API data.
     *
     * @return ProductAdapter
     */
    protected function getAdapter() : DataSourceAdapterContract
    {
        return new ProductAdapter(new Product());
    }
}
