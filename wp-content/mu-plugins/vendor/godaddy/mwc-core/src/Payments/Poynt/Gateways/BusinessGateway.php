<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways;

use Exception;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters\GetBusinessRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Models\Business;
use GoDaddy\WordPress\MWC\Payments\Gateways\AbstractGateway;

/**
 * The business gateway.
 */
class BusinessGateway extends AbstractGateway
{
    use CanGetNewInstanceTrait;

    /**
     * Get the connected business.
     *
     * @return Business
     * @throws Exception
     */
    public function get() : Business
    {
        return $this->doAdaptedRequest($this, GetBusinessRequestAdapter::getNewInstance(Business::getNewInstance()));
    }
}
