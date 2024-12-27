<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\MissingRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\CatalogRequest;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Models\Catalog;

/**
 * An adapter for converting the core product gateway to and from Poynt API data.
 */
class CatalogRequestAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var Catalog|null */
    protected $source;

    /**
     * Constructs the adapter.
     *
     * @param Catalog $catalog
     */
    public function __construct(Catalog $catalog)
    {
        $this->source = $catalog;
    }

    /**
     * Converts the source catalog to Poynt API catalog request.
     *
     * @return CatalogRequest
     * @throws MissingRemoteIdException|Exception
     */
    public function convertFromSource() : CatalogRequest
    {
        if (! $catalogId = $this->source->getRemoteId()) {
            throw new MissingRemoteIdException('The source catalog must have a remote ID');
        }

        return (new CatalogRequest($catalogId))->setBody((new CatalogAdapter($this->source))->convertFromSource());
    }

    /**
     * Converts the Poynt API catalog response to a Catalog object.
     *
     * @param Response|null $response
     * @return Catalog
     */
    public function convertToSource(Response $response = null) : Catalog
    {
        return (new CatalogAdapter($this->source))->convertToSource($response && $response->getBody() ? $response->getBody() : []);
    }
}
