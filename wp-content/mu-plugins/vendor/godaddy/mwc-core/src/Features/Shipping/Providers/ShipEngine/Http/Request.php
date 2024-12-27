<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Shipping\Providers\ShipEngine\Http;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Http\GoDaddyRequest;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformEnvironment;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;

class Request extends GoDaddyRequest
{
    public $timeout = 10;

    /** @var class-string<ResponseContract> the type of response the request should return */
    protected $responseClass = Response::class;

    /**
     * Sends the request.
     *
     * @return Response
     * @throws Exception
     */
    public function send()
    {
        if (empty($this->url)) {
            $this->setUrl($this->getBaseUrl());
        }

        /** @var Response $response */
        $response = parent::send();

        return $response;
    }

    /**
     * Gets the base URL for ShipEngine API requests.
     */
    protected function getBaseUrl() : string
    {
        $environment = ManagedWooCommerceRepository::getEnvironment();

        if (in_array($environment, [PlatformEnvironment::TEST, PlatformEnvironment::LOCAL], true)) {
            $apiUrl = Configuration::get('shipping.shipengine.api.url.dev');
        } else {
            $apiUrl = Configuration::get('shipping.shipengine.api.url.prod');
        }

        return TypeHelper::string($apiUrl, '');
    }
}
