<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\ExternalCheckout;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

abstract class AbstractExternalCheckoutIntegration
{
    use CanGetNewInstanceTrait;

    /**
     * Decides if the integration should be available.
     *
     * @param string $context used in concrete implementations
     * @return bool
     */
    abstract public function isAvailable(string $context) : bool;

    /**
     * Renders the integration controls.
     */
    abstract public function render() : void;
}
