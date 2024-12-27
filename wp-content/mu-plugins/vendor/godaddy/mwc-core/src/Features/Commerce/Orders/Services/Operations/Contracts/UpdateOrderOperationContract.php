<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Operations\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Contracts\HasRequiredOrderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\OrdersService;

/**
 * Contract for getting and setting properties needed by the {@see OrdersService} to update an order.
 */
interface UpdateOrderOperationContract extends HasRequiredOrderContract
{
    /**
     * Gets the new WooCommerce order status.
     *
     * @return string
     */
    public function getNewWooCommerceOrderStatus() : string;

    /**
     * Sets the new WooCommerce order status.
     *
     * @param string $value
     * @return $this
     */
    public function setNewWooCommerceOrderStatus(string $value);

    /**
     * Gets the old WooCommerce order status.
     *
     * @return string
     */
    public function getOldWooCommerceOrderStatus() : string;

    /**
     * Sets the old WooCommerce order status.
     *
     * @param string $value
     * @return $this
     */
    public function setOldWooCommerceOrderStatus(string $value);
}
