<?php

namespace GoDaddy\WordPress\MWC\Shipping\Gateways;

use Exception;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayRequestAdapterContract;
use GoDaddy\WordPress\MWC\Shipping\Exceptions\Contracts\ShippingExceptionContract;
use GoDaddy\WordPress\MWC\Shipping\Exceptions\ShippingGatewayException;
use GoDaddy\WordPress\MWC\Shipping\Traits\AdaptsRequestsTrait;

/**
 * Allows classes to adapt requests responses.
 */
abstract class AbstractGateway
{
    use AdaptsRequestsTrait;

    /**
     * Preforms the request and returns the adapted response.
     *
     * @param GatewayRequestAdapterContract $adapter
     * @return mixed
     * @throws ShippingExceptionContract
     */
    protected function doAdaptedRequest(GatewayRequestAdapterContract $adapter)
    {
        return $adapter->convertToSource($this->doRequest($adapter->convertFromSource()));
    }

    /**
     * Performs a request.
     *
     * @param RequestContract $request request object
     *
     * @return ResponseContract
     * @throws ShippingGatewayException
     */
    protected function doRequest(RequestContract $request) : ResponseContract
    {
        try {
            /** @var ResponseContract $response */
            $response = $request->send();
        } catch (Exception $exception) {
            throw new ShippingGatewayException($exception->getMessage(), $exception);
        }

        return $response;
    }
}
