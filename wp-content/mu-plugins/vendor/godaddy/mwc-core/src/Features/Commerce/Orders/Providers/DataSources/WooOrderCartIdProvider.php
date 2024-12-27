<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use WC_Order;

class WooOrderCartIdProvider
{
    use CanGetNewInstanceTrait;

    public const CART_ID_META_KEY = '_mwc_cart_id';

    /**
     * Gets the cart ID from the order metadata.
     *
     * @param WC_Order $wooOrder
     *
     * @return non-empty-string|null
     */
    public function getCartId(WC_Order $wooOrder) : ?string
    {
        $meta = $wooOrder->get_meta(static::CART_ID_META_KEY);

        return TypeHelper::string($meta, '') ?: null;
    }

    /**
     * Sets the cart ID from the order metadata.
     *
     * @param WC_Order $wooOrder
     * @param string   $value
     *
     * @return WC_Order
     */
    public function setCartId(WC_Order $wooOrder, string $value) : WC_Order
    {
        $wooOrder->update_meta_data(static::CART_ID_META_KEY, $value);

        return $wooOrder;
    }
}
