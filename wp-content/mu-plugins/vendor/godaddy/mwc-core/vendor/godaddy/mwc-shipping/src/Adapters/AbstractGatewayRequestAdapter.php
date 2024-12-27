<?php

namespace GoDaddy\WordPress\MWC\Shipping\Adapters;

use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayRequestAdapterContract;
use GoDaddy\WordPress\MWC\Shipping\Exceptions\ShippingException;

abstract class AbstractGatewayRequestAdapter implements GatewayRequestAdapterContract
{
    /**
     * Converts gateway response to source.
     *
     * @param ?ResponseContract $response
     * @return mixed
     * @throws ShippingException
     */
    public function convertToSource(?ResponseContract $response = null)
    {
        if (! $response) {
            return null;
        }

        $this->throwIfIsErrorResponse($response);

        return $this->convertResponse($response);
    }

    /**
     * Converts gateway response to source.
     *
     * @param ResponseContract $response
     * @return mixed
     * @throws ShippingException
     */
    abstract protected function convertResponse(ResponseContract $response);

    /**
     * @param ResponseContract $response
     * @return void
     * @throws ShippingException
     */
    protected function throwIfIsErrorResponse(ResponseContract $response) : void
    {
        if ($response->isError()) {
            throw new ShippingException($response->getErrorMessage() ?: 'Shipping Error. The server responded with status: '.$response->getStatus());
        }
    }
}
