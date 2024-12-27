<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\AbstractProductRequest;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\CreateProductRequest;

/**
 * An adapter for converting the core product object to and from Poynt API CreateProductRequest.
 */
class CreateProductRequestAdapter extends AbstractProductRequestAdapter
{
    /**
     * Converts the source product to Poynt API CreateProductRequest.
     *
     * @return AbstractProductRequest
     * @throws Exception
     */
    public function convertFromSource() : AbstractProductRequest
    {
        return $this->getProductRequest()->setBody($this->getProductAdapter()->convertFromSource());
    }

    /**
     * {@inheritDoc}
     */
    protected function getProductRequest() : AbstractProductRequest
    {
        return new CreateProductRequest();
    }
}
