<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways\CatalogsGateway;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\AbstractResourceRequest;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\CatalogRequest;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Models\Catalog;

class CatalogListRequestAdapter extends AbstractListRequestAdapter
{
    /**
     * Constructs the adapter.
     *
     * @param CatalogsGateway $catalogsGateway
     */
    public function __construct(CatalogsGateway $catalogsGateway)
    {
        $this->source = $catalogsGateway;
        $this->responseBodyKey = CatalogRequest::RESOURCE_PLURAL;
    }

    /**
     * Returns the request used to get a remote resource.
     *
     * @return CatalogRequest
     */
    protected function getRequest() : AbstractResourceRequest
    {
        return new CatalogRequest();
    }

    /**
     * Returns the adapter for converting the Catalog object to and from Poynt API data.
     *
     * @return CatalogAdapter
     */
    protected function getAdapter() : DataSourceAdapterContract
    {
        return new CatalogAdapter(new Catalog());
    }
}
