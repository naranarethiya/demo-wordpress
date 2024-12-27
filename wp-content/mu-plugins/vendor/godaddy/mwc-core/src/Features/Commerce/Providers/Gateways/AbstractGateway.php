<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Gateways;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;

abstract class AbstractGateway
{
    /**
     * Preforms the request and returns the adapted response.
     *
     * @return mixed
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    protected function doAdaptedRequest(AbstractGatewayRequestAdapter $adapter)
    {
        return $adapter->convertToSource($this->doRequest($adapter->convertFromSource()));
    }

    /**
     * Performs a request.
     *
     * @param RequestContract $request request object
     *
     * @return ResponseContract
     * @throws BaseException|Exception
     */
    protected function doRequest(RequestContract $request) : ResponseContract
    {
        return $request->send();
    }
}
