<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\MissingRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\AbstractProductRequest;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\GetProductRequest;

/**
 * An adapter for converting the core product object to and from Poynt API GetProductRequest.
 */
class GetProductRequestAdapter extends AbstractProductRequestAdapter
{
    /**
     * Converts the source product to Poynt API GetProductRequest.
     *
     * @return AbstractProductRequest
     * @throws MissingRemoteIdException|Exception
     */
    public function convertFromSource() : AbstractProductRequest
    {
        if (! $this->source->getRemoteId()) {
            throw new MissingRemoteIdException('The source product must have a remote ID');
        }

        return $this->getProductRequest();
    }

    /**
     * {@inheritDoc}
     */
    protected function getProductRequest() : AbstractProductRequest
    {
        return new GetProductRequest($this->source->getRemoteId());
    }
}
