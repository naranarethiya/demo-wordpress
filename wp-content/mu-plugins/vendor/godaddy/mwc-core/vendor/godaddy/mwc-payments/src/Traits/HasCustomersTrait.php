<?php

namespace GoDaddy\WordPress\MWC\Payments\Traits;

use GoDaddy\WordPress\MWC\Payments\Gateways\AbstractGateway;

/**
 * Has customers trait.
 */
trait HasCustomersTrait
{
    /** @var class-string<AbstractGateway> customers gateway class */
    protected $customersGateway;

    /**
     * Gets the customers gateway instance.
     *
     * @return AbstractGateway
     */
    public function customers() : AbstractGateway
    {
        return new $this->customersGateway();
    }
}
