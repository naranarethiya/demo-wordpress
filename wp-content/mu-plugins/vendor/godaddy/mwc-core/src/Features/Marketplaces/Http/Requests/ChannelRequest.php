<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Http\Requests;

use Exception;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Http\GoDaddyRequest;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * The GoDaddy channel request class.
 */
class ChannelRequest extends GoDaddyRequest
{
    use CanGetNewInstanceTrait;

    /**
     * Sends the request.
     *
     * @return ResponseContract
     * @throws Exception
     */
    public function send() : ResponseContract
    {
        if (empty($this->url)) {
            $this->setUrl(ManagedWooCommerceRepository::getApiUrl());
        }

        return parent::send();
    }
}
