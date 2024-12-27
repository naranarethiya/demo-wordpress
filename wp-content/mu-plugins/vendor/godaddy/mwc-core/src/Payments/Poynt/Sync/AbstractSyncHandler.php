<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Sync;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

abstract class AbstractSyncHandler
{
    /** @var string name */
    protected static $name;

    /**
     * Gets isEnabled.
     *
     * @return bool
     */
    public static function isEnabled() : bool
    {
        return (bool) Configuration::get('payments.godaddy-payments-payinperson.sync.'.static::$name.'.enabled', false);
    }

    /**
     * Sets isEnabled.
     *
     * @param bool $value
     */
    public static function setIsEnabled(bool $value)
    {
        Configuration::set('payments.godaddy-payments-payinperson.sync.'.static::$name.'.enabled', $value);
    }

    /**
     * Gets isSyncing.
     *
     * @return bool
     */
    public static function isSyncing() : bool
    {
        return (bool) Configuration::get('payments.godaddy-payments-payinperson.sync.'.static::$name.'.isSyncing', false);
    }

    /**
     * Sets isSyncing.
     *
     * @param bool $value
     */
    public static function setIsSyncing(bool $value)
    {
        update_option('mwc_payments_sync_'.static::$name.'_isSyncing', wc_bool_to_string($value));

        Configuration::set('payments.godaddy-payments-payinperson.sync.'.static::$name.'.isSyncing', $value);
    }

    /**
     * Gets isHealthy.
     *
     * @return bool
     */
    public static function isHealthy() : bool
    {
        return (bool) Configuration::get('payments.godaddy-payments-payinperson.sync.'.static::$name.'.isHealthy', false);
    }

    /**
     * Sets isHealthy.
     *
     * @param bool $value
     */
    public static function setIsHealthy(bool $value)
    {
        update_option('mwc_payments_sync_'.static::$name.'_isHealthy', wc_bool_to_string($value));

        Configuration::set('payments.godaddy-payments-payinperson.sync.'.static::$name.'.isHealthy', $value);
    }

    /**
     * Gets the enabled catalog IDs.
     *
     * @return string[]
     */
    public static function getEnabledCatalogIds() : array
    {
        return (array) Configuration::get('payments.godaddy-payments-payinperson.sync.'.static::$name.'.enabledCatalogIds', []);
    }

    /**
     * Sets the enabled catalog IDs.
     *
     * @param string[] $catalogIds
     */
    public static function setEnabledCatalogIds(array $catalogIds)
    {
        Configuration::set('payments.godaddy-payments-payinperson.sync.'.static::$name.'.enabledCatalogIds', $catalogIds);
    }

    /**
     * Gets syncedCatalogIds.
     *
     * @return string[]
     */
    public static function getSyncedCatalogIds() : array
    {
        return (array) Configuration::get('payments.godaddy-payments-payinperson.sync.'.static::$name.'.syncedCatalogIds', []);
    }

    /**
     * Sets syncedCatalogIds.
     *
     * @param string[] $catalogIds
     */
    public static function setSyncedCatalogIds(array $catalogIds)
    {
        update_option('mwc_payments_sync_'.static::$name.'_syncedCatalogIds', $catalogIds);

        Configuration::set('payments.godaddy-payments-payinperson.sync.'.static::$name.'.syncedCatalogIds', $catalogIds);
    }

    /**
     * Determines is a product should be sync'ed.
     *
     * @param Product $product
     * @return bool
     */
    public static function shouldSyncProduct(Product $product) : bool
    {
        if ('simple' !== $product->getType()) {
            return false;
        }

        foreach ($product->getAttributeData() as $attribute) {
            if (ArrayHelper::get($attribute, 'isCustomPrice', false)) {
                return false;
            }
            if (ArrayHelper::get($attribute, 'cardinality', 1) !== 1) {
                return false;
            }
        }

        /*
         * Filter whether the product should be synced
         *
         * @param bool true Whether the product should sync
         * @param Product $product Product object
         */
        return (bool) apply_filters('mwc_payments_godaddy_payments_should_sync_product', true, $product);
    }
}
