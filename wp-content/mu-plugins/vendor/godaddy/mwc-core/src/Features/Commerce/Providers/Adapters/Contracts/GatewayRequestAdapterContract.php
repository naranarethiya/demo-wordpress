<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\Contracts;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;

/**
 * Gateway request adapter contract.
 */
interface GatewayRequestAdapterContract extends DataSourceAdapterContract
{
    /**
     * Creates a request object for the operation associated with the adapter.
     *
     * @return RequestContract
     * @throws CommerceExceptionContract
     */
    public function convertFromSource() : RequestContract;

    /**
     * Converts the given response object into the return value expected from the operation associated with the adapter.
     *
     * @param ?ResponseContract $response
     * @return mixed
     * @throws CommerceExceptionContract
     */
    public function convertToSource(?ResponseContract $response = null);
}
