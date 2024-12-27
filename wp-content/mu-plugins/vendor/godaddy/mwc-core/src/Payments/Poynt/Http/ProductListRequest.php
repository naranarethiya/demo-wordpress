<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;

/**
 * Request to get a list of remote Poynt Products.
 */
class ProductListRequest extends AbstractProductRequest
{
    /**
     * ProductListRequest constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->setMethod('GET');

        parent::__construct();
    }
}
