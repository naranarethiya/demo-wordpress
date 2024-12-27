<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Http\Requests;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformEnvironment;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Http\GraphQL\Requests\AbstractRequest;

class Request extends AbstractRequest
{
    /**
     * {@inheritDoc}
     */
    public function getBaseUrl() : string
    {
        $environment = ManagedWooCommerceRepository::getEnvironment();

        if (in_array($environment, [PlatformEnvironment::TEST, PlatformEnvironment::LOCAL], true)) {
            $apiUrl = Configuration::get('commerce.gateway.api.url.dev');
        } else {
            $apiUrl = Configuration::get('commerce.gateway.api.url.prod');
        }

        return TypeHelper::string($apiUrl, '');
    }

    /**
     * {@inheritDoc}
     */
    protected function getPathPrefix() : string
    {
        return '/v1/commerce/proxy/order-subgraph';
    }
}
