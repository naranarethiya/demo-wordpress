<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Http\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\HasStoreIdentifierTrait;

trait IsCommerceRequestTrait
{
    use HasStoreIdentifierTrait;

    /**
     * getBaseUrl for API endpoint.
     *
     * @return string
     */
    abstract protected function getBaseUrl() : string;

    /**
     * getPrefix for API endpoint.
     *
     * @return string
     */
    protected function getPathPrefix() : string
    {
        return "/v1/commerce/stores/{$this->storeId}";
    }

    /**
     * Sends the request.
     *
     * @return ResponseContract
     * @throws Exception
     */
    public function send() : ResponseContract
    {
        if (empty($this->url)) {
            $this->setUrl($this->getBaseUrl().$this->getPathPrefix());
        }

        return parent::send();
    }
}
