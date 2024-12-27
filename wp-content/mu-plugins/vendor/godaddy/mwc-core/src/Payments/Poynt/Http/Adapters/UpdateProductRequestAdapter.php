<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\MissingRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\AbstractProductRequest;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\UpdateProductRequest;

/**
 * An adapter for converting the core product object to and from Poynt API UpdateProductRequest.
 */
class UpdateProductRequestAdapter extends AbstractProductRequestAdapter
{
    /**
     * Converts the source product to Poynt API UpdateProductRequest.
     *
     * @return AbstractProductRequest
     * @throws MissingRemoteIdException|Exception
     */
    public function convertFromSource() : AbstractProductRequest
    {
        if (! $this->source->getRemoteId()) {
            throw new MissingRemoteIdException('The source product must have a remote ID');
        }

        $productData = $this->getProductAdapter()->convertFromSource();

        // TODO: consider creating a dedicated adapter for creating JSON patches {@itambek 2022-02-11}
        return $this->getProductRequest()->setBody(array_map(static function ($key, $value) {
            return [
                'op'    => 'replace',
                'path'  => "/{$key}",
                'value' => $value,
            ];
        }, array_keys($productData), $productData));
    }

    /**
     * {@inheritDoc}
     */
    protected function getProductRequest() : AbstractProductRequest
    {
        return new UpdateProductRequest($this->source->getRemoteId());
    }
}
