<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Providers\Traits;

use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways\CatalogsGateway;

trait HasCatalogsTrait
{
    /**
     * Gets an instance of the Catalogs Gateway.
     *
     * @return CatalogsGateway
     */
    public function catalogs() : CatalogsGateway
    {
        return new CatalogsGateway();
    }
}
