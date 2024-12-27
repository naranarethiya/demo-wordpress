<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\GoDaddy\Http;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetEnvironmentBasedConfigValueTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Http\Requests\AbstractRequest;

class Request extends AbstractRequest
{
    use CanGetEnvironmentBasedConfigValueTrait;

    /**
     * Constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $timeout = $this->getEnvironmentConfigValue('commerce.gateway.api.timeout');

        $this->setTimeout(TypeHelper::int($timeout, 10));
    }

    /**
     * {@inheritDoc}
     */
    protected function getBaseUrl() : string
    {
        return TypeHelper::string($this->getEnvironmentConfigValue('commerce.gateway.api.url'), '');
    }

    /**
     * This proxy's through the MWC API for 2-legged oauth.
     *
     * @return string
     */
    protected function getPathPrefix() : string
    {
        return '/v1/commerce/proxy/apis';
    }
}
