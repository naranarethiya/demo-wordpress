<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\GoDaddy\Http\Requests;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformEnvironment;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Http\Requests\AbstractRequest;

/**
 * Commerce Customers Request class.
 */
class Request extends AbstractRequest
{
    /**
     * {@inheritDoc}
     */
    protected function getBaseUrl() : string
    {
        $environment = ManagedWooCommerceRepository::getEnvironment();

        if (in_array($environment, [PlatformEnvironment::TEST, PlatformEnvironment::LOCAL], true)) {
            $apiUrl = Configuration::get('commerce.customers.api.url.dev');
        } else {
            $apiUrl = Configuration::get('commerce.customers.api.url.prod');
        }

        return TypeHelper::string($apiUrl, '');
    }

    /**
     * {@inheritDoc}
     */
    protected function getPathPrefix() : string
    {
        return '/v1/commerce/customers/proxy/stores/'.$this->storeId;
    }
}
