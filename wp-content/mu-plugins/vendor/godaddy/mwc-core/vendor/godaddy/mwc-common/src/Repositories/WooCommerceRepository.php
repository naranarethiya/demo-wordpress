<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories;

use Automattic\WooCommerce\Utilities\OrderUtil;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\AddressAdapter;
use GoDaddy\WordPress\MWC\Common\Exceptions\WooCommerceCartException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Address;
use WC_Cart;
use WC_Helper_Options;
use WooCommerce;

/**
 * WooCommerce repository class.
 */
class WooCommerceRepository
{
    /**
     * Retrieve the current WooCommerce instance.
     *
     * @return WooCommerce|null
     */
    public static function getInstance() : ?WooCommerce
    {
        if (! static::isWooCommerceActive()) {
            return null;
        }

        return WC();
    }

    /**
     * Gets the current cart instance.
     *
     * @return WC_Cart
     * @throws WooCommerceCartException
     */
    public static function getCartInstance() : WC_Cart
    {
        $wc = static::getInstance();

        if (! $wc || ! $wc->cart instanceof WC_Cart) {
            throw new WooCommerceCartException(__('WooCommerce cart is not available', 'mwc-common'));
        }

        return $wc->cart;
    }

    /**
     * Retrieves the configured WooCommerce country code.
     *
     * @NOTE: If this method is called at some phases like plugin loading, $wc->countries may not
     *        be available yet, which means the base country will be queried from the WP option directly. The result
     *        may vary from the results called from another phases, considering that it won't be passed to the chained
     *        filters called by get_base_country().
     *
     * @return string
     */
    public static function getBaseCountry() : string
    {
        if (! empty($wc = static::getInstance()) && $wc->countries) {
            return $wc->countries->get_base_country();
        }

        return StringHelper::before(get_option('woocommerce_default_country', ''), ':');
    }

    /**
     * Retrieves the configured WooCommerce currency code.
     *
     * @return string
     */
    public static function getCurrency() : string
    {
        return static::isWooCommerceActive() ? get_woocommerce_currency() : '';
    }

    /**
     * Gets the configured WooCommerce weight unit.
     *
     * @return string
     */
    public static function getWeightUnit() : string
    {
        if (! static::isWooCommerceActive()) {
            return '';
        }

        $unit = get_option('woocommerce_weight_unit', '');

        return is_string($unit) ? $unit : '';
    }

    /**
     * Retrieves the current WooCommerce access token.
     *
     * @return string|null
     */
    public static function getWooCommerceAccessToken() : ?string
    {
        $authorization = self::getWooCommerceAuthorization();

        return ArrayHelper::get($authorization, 'access_token');
    }

    /**
     * Retrieves the current WooCommerce Authorization Object.
     *
     * @return null|array
     */
    public static function getWooCommerceAuthorization() : ?array
    {
        if (class_exists('WC_Helper_Options')) {
            return ArrayHelper::wrap(WC_Helper_Options::get('auth')) ?: null;
        }

        return null;
    }

    /**
     * Checks if the WooCommerce plugin is active.
     *
     * @return bool
     */
    public static function isWooCommerceActive() : bool
    {
        return null !== Configuration::get('woocommerce.version') && class_exists(WooCommerce::class);
    }

    /**
     * Checks if the site is connected to WooCommerce.com.
     *
     * @return bool
     */
    public static function isWooCommerceConnected() : bool
    {
        return self::isWooCommerceActive() && self::getWooCommerceAccessToken();
    }

    /**
     * Checks whether the current page is a WooCommerce admin page.
     *
     * This method should return true for all admin pages that have a URL like
     * /wp-admin/admin.php?page=wc-admin&path={somepath} (where path is optional).
     *
     * @param string|null $path optional string to compare with the path query parameter
     * @return bool
     */
    public static function isWooCommerceAdminPage(?string $path = null) : bool
    {
        if (! $screen = get_current_screen()) {
            return false;
        }

        if ($screen->base !== 'woocommerce_page_wc-admin') {
            return false;
        }

        return ! $path || $path === ArrayHelper::get($_REQUEST, 'path', '');
    }

    /**
     * Determines if the current page is a front-end WooCommerce page.
     *
     * @return bool
     */
    public static function isWooCommercePage() : bool
    {
        return static::isWooCommerceActive() && (is_woocommerce() || static::isCartPage() || static::isCheckoutPage() || static::isCheckoutPayPage() || static::isOrderReceivedPage());
    }

    /**
     * Gets the ID of a WooCommerce order's admin edit screen.
     *
     * @return string
     */
    public static function getEditOrderPageScreenId() : string
    {
        return function_exists('wc_get_page_screen_id')
            ? wc_get_page_screen_id('shop_order')
            : 'shop_order';
    }

    /**
     * Gets the cart page URl.
     *
     * @return string|null
     */
    public static function getCartPageUrl() : ?string
    {
        return static::isWooCommerceActive() ? wc_get_cart_url() : null;
    }

    /**
     * Determines if the current page is the WooCommerce cart page.
     *
     * @return bool
     */
    public static function isCartPage() : bool
    {
        return static::isWooCommerceActive() && is_cart();
    }

    /**
     * Gets the checkout page URl.
     *
     * @return string|null
     */
    public static function getCheckoutPageUrl() : ?string
    {
        return static::isWooCommerceActive() ? wc_get_checkout_url() : null;
    }

    /**
     * Determines if the current page is the WooCommerce checkout page.
     *
     * @return bool
     */
    public static function isCheckoutPage() : bool
    {
        return static::isWooCommerceActive() && is_checkout();
    }

    /**
     * Determines if the current page is the WooCommerce checkout pay page.
     *
     * @return bool
     */
    public static function isCheckoutPayPage() : bool
    {
        return static::isWooCommerceActive() && is_checkout_pay_page();
    }

    /**
     * Determines if the current page is a WooCommerce product page.
     *
     * @return bool
     */
    public static function isProductPage() : bool
    {
        return static::isWooCommerceActive() && is_product();
    }

    /**
     * Determines if the current page is for the order received page.
     *
     * @return bool
     */
    public static function isOrderReceivedPage() : bool
    {
        return static::isWooCommerceActive() && is_order_received_page();
    }

    /**
     * Determines if the custom orders table are enabled (HPOS).
     *
     * If not, it means the store is using the default WordPress posts table for orders.
     *
     * @return bool
     */
    public static function isCustomOrdersTableUsageEnabled() : bool
    {
        return class_exists(OrderUtil::class) && OrderUtil::custom_orders_table_usage_is_enabled();
    }

    /**
     * Returns WooCommerce version.
     *
     * @return string|null
     */
    public static function getWooCommerceVersion() : ?string
    {
        return Configuration::get('woocommerce.version', '');
    }

    /**
     * Gets the WooCommerce shop address.
     *
     * @return Address
     */
    public static function getShopAddress() : Address
    {
        $countryState = explode(':', TypeHelper::string(get_option('woocommerce_default_country'), ''));
        $country = $countryState[0] ?? '';
        $state = $countryState[1] ?? '';

        $addressLine1 = TypeHelper::string(get_option('woocommerce_store_address'), '');
        $addressLine2 = TypeHelper::string(get_option('woocommerce_store_address_2'), '');

        $city = TypeHelper::string(get_option('woocommerce_store_city'), '');
        $postCode = TypeHelper::string(get_option('woocommerce_store_postcode'), '');

        return AddressAdapter::getNewInstance([
            'address_1' => $addressLine1,
            'address_2' => $addressLine2,
            'city'      => $city,
            'postcode'  => $postCode,
            'state'     => $state,
            'country'   => $country,
        ])->convertFromSource();
    }

    /**
     * Returns the WooCommerce API URL.
     *
     * @param string $request
     * @param bool|null $ssl
     * @return string
     */
    public static function getApiUrl(string $request, ?bool $ssl = null) : string
    {
        $wc = static::getInstance();

        if ($wc) {
            return $wc->api_request_url($request, $ssl);
        }

        return '';
    }

    /**
     * Gets an array containing information about a shipping method.
     *
     * @param string $methodId
     * @param string $instanceId
     * @return array<string,mixed>
     */
    public static function getShippingMethodInstance(string $methodId, string $instanceId) : array
    {
        return ArrayHelper::wrap(get_option(sprintf('woocommerce_%s_%d_settings', $methodId, $instanceId), []));
    }
}
