<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Http\Requests;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformEnvironment;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Http\Requests\AbstractRequest;

/**
 * Commerce Inventory Request class.
 */
class Request extends AbstractRequest
{
    /**
     * Constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        if (in_array(ManagedWooCommerceRepository::getEnvironment(), [PlatformEnvironment::TEST, PlatformEnvironment::LOCAL], true)) {
            $timeout = Configuration::get('commerce.inventory.api.timeout.dev');
        } else {
            $timeout = Configuration::get('commerce.inventory.api.timeout.prod');
        }

        $this->setTimeout(TypeHelper::int($timeout, 10));
    }

    /**
     * Builds a valid url string with parameters.
     *
     * @return string
     * @throws Exception
     */
    public function buildUrlString() : string
    {
        /*
         * unset the locale to prevent a `locale` query arg from being added
         * this can be removed after decoupling from {@see GoDaddyRequest::buildUrlString()}
         */
        $this->locale = '';

        return parent::buildUrlString();
    }

    /**
     * {@inheritDoc}
     */
    protected function getBaseUrl() : string
    {
        $environment = ManagedWooCommerceRepository::getEnvironment();

        if (in_array($environment, [PlatformEnvironment::TEST, PlatformEnvironment::LOCAL], true)) {
            $apiUrl = Configuration::get('commerce.inventory.api.url.dev');
        } else {
            $apiUrl = Configuration::get('commerce.inventory.api.url.prod');
        }

        return TypeHelper::string($apiUrl, '');
    }

    /**
     * {@inheritDoc}
     *
     * Overridden to allow the commerce.inventory.api.url values to define the base. This ensures the ability to switch
     * between the MWC API proxy & direct inventory service using the MWC_COMMERCE_INVENTORY_SERVICE_URL constant.
     */
    protected function getPathPrefix() : string
    {
        return '/stores/'.$this->storeId;
    }
}
