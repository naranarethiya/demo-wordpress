<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\API\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\WooCommerceCartException;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;

/**
 * A trait for API controllers that need to initialize the WooCommerce cart.
 */
trait InitializesCartTrait
{
    /**
     * Initializes the WooCommerce cart.
     *
     * @throws Exception|WooCommerceCartException
     */
    protected function initializeCart() : void
    {
        $this->maybeLoadCart();

        $cart = WooCommerceRepository::getCartInstance();

        // ensure all properties are initialized
        $cart->get_cart();
        $cart->calculate_fees();
        $cart->calculate_shipping();
        $cart->calculate_totals();
    }

    /**
     * Loads the WooCommerce cart functionality if not already loaded.
     */
    protected function maybeLoadCart() : void
    {
        // if this action was fired WooCommerce has already taken care of it
        if (did_action('woocommerce_load_cart_from_session')) {
            return;
        }

        wc_load_cart();
    }
}
