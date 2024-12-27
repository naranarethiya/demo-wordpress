<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;

/**
 * Request to update a remote Poynt Product.
 */
class UpdateProductRequest extends AbstractProductRequest
{
    /**
     * UpdateProductRequest constructor.
     *
     * @param string $productId
     * @throws Exception
     */
    public function __construct(string $productId)
    {
        $this->setMethod('PATCH');

        parent::__construct($productId);
    }
}
