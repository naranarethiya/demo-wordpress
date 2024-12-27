<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;

/**
 * Request to get a remote Poynt Product.
 */
class GetProductRequest extends AbstractProductRequest
{
    /**
     * GetProductRequest constructor.
     *
     * @param string $productId
     * @throws Exception
     */
    public function __construct(string $productId)
    {
        $this->setMethod('GET');

        parent::__construct($productId);
    }
}
