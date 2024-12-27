<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * Request to get a remote Poynt Catalog.
 */
class CatalogRequest extends AbstractResourceRequest
{
    use CanGetNewInstanceTrait;

    /** @var string */
    const RESOURCE_PLURAL = 'catalogs';

    /**
     * CatalogRequest constructor.
     *
     * @param string|null $catalogId
     * @throws Exception
     */
    public function __construct(string $catalogId = null)
    {
        $this->setMethod('GET');

        parent::__construct(static::RESOURCE_PLURAL, $catalogId);
    }
}
