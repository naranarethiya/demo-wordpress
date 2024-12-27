<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * Request to get a remote Poynt Catalog.
 */
class GetCatalogRequest extends CatalogRequest
{
    use CanGetNewInstanceTrait;

    /**
     * GetCatalogRequest constructor.
     *
     * @param string|null $catalogId
     * @param bool $getFull Optional. Whether to get the full catalog. Defaults to true.
     * @throws Exception
     */
    public function __construct(string $catalogId = null, bool $getFull = true)
    {
        if ($getFull) {
            $this->route = 'full';
        }

        parent::__construct($catalogId);
    }
}
