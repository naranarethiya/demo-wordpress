<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Traits;

/**
 * A trait for auto-enabling core payment gateways.
 */
trait CanAutoEnablePaymentGatewayTrait
{
    /**
     * Checks whether the payment gateway should be auto enabled. Can be overridden in abstract gateway class.
     *
     * @return bool
     */
    public function shouldAutoEnable() : bool
    {
        return false;
    }

    /**
     * Updates whether the payment gateway is enabled.
     *
     * @return void
     */
    public function autoEnable() : void
    {
        $this->update_option('enabled', 'yes');
    }
}
