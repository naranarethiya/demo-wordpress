<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters;

use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequest404Exception;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequestException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\Contracts\GatewayRequestAdapterContract;

abstract class AbstractGatewayRequestAdapter implements GatewayRequestAdapterContract
{
    /**
     * {@inheritDoc}
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
     * @throws CommerceExceptionContract
     */
    abstract protected function convertResponse(ResponseContract $response);

    /**
     * Throws an exception on error responses.
     *
     * @param ResponseContract $response
     * @return void
     * @throws CommerceExceptionContract
     */
    protected function throwIfIsErrorResponse(ResponseContract $response) : void
    {
        if ($response->isError()) {
            if (404 === $response->getStatus()) {
                throw new GatewayRequest404Exception($this->getErrorMessageFromResponse($response));
            } else {
                throw new GatewayRequestException($this->getErrorMessageFromResponse($response));
            }
        }
    }

    /**
     * Generates an error error message from the given response.
     */
    protected function getErrorMessageFromResponse(ResponseContract $response) : string
    {
        return $response->getErrorMessage() ?: 'Request Error. The server responded with status: '.$response->getStatus();
    }
}
