<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;

/**
 * Request to create a remote Poynt Product.
 */
class CreateProductRequest extends AbstractProductRequest
{
    /**
     * CreateProductRequest constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->setMethod('POST');

        parent::__construct();
    }
}
