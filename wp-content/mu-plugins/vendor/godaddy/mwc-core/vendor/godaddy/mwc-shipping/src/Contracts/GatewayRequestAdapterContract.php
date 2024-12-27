<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Shipping\Exceptions\ShippingException;

/**
 * Gateway request adapter contract.
 */
interface GatewayRequestAdapterContract extends DataSourceAdapterContract
{
    /**
     * Converts gateway request from source.
     *
     * @return RequestContract
     * @throws ShippingException
     */
    public function convertFromSource() : RequestContract;

    /**
     * Converts gateway response to source.
     *
     * @param ?ResponseContract $response
     * @return mixed
     * @throws ShippingException
     */
    public function convertToSource(?ResponseContract $response = null);
}
