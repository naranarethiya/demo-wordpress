<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use WC_Cart;

/**
 * Repository for handling the WooCommerce cart.
 */
class CartRepository
{
    /**
     * Gets the cart instance.
     *
     * @return WC_Cart
     * @throws Exception
     */
    public static function getInstance() : WC_Cart
    {
        $wc = WooCommerceRepository::getInstance();

        if (! $wc || empty($wc->cart) || ! $wc->cart instanceof WC_Cart) {
            throw new Exception(__('WooCommerce cart is not available', 'mwc-common'));
        }

        return $wc->cart;
    }

    /**
     * Initializes the WooCommerce cart.
     *
     * @throws Exception
     */
    public static function initialize() : WC_Cart
    {
        static::maybeLoad();

        $cart = static::getInstance();

        // ensure all properties are initialized
        $cart->get_cart();
        $cart->calculate_fees();
        $cart->calculate_shipping();
        $cart->calculate_totals();

        return $cart;
    }

    /**
     * Loads the WooCommerce cart functionality if not already loaded.
     */
    protected static function maybeLoad()
    {
        // if this action was fired WooCommerce has already taken care of it
        if (did_action('woocommerce_load_cart_from_session')) {
            return;
        }

        wc_load_cart();
    }

    /**
     * Determines if the current cart needs shipping.
     *
     * @return bool
     */
    public static function needsShipping() : bool
    {
        try {
            return static::getInstance()->needs_shipping();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Determines whether the cart is empty.
     *
     * @return bool
     */
    public static function isEmpty() : bool
    {
        try {
            return static::getInstance()->is_empty();
        } catch (Exception $e) {
            return true;
        }
    }

    /**
     * Empties the cart.
     */
    public static function empty()
    {
        try {
            static::getInstance()->empty_cart();
        } catch (Exception $e) {
        }
    }

    /**
     * Adds a product to cart.
     *
     * @param int $productId
     * @param float $quantity
     * @param int $variationId
     * @param array $variationData
     * @param array $cartItemData
     * @return string
     * @throws Exception
     */
    public static function addProduct(int $productId, float $quantity = 1, int $variationId = 0, array $variationData = [], array $cartItemData = []) : string
    {
        $cartItemKey = static::getInstance()->add_to_cart(
            $productId,
            $quantity,
            $variationId,
            $variationData,
            $cartItemData
        );

        if (! is_string($cartItemKey)) {
            // WooCommerce will automatically print a notice in the front end with more details which are not available here
            throw new Exception(__('The product could not be added to the cart.', 'mwc-common'));
        }

        return $cartItemKey;
    }

    /**
     * Returns the current cart hash.
     *
     * @return string|null
     */
    public static function getHash() : ?string
    {
        try {
            $instance = static::getInstance();
        } catch (Exception $exception) {
            return null;
        }

        return $instance->get_cart_hash();
    }
}
