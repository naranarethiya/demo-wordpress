<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce;

use Automattic\WooCommerce\Admin\API\Reports\Stock\Stats\Query;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use Throwable;

/**
 * Repository for handling access to the {@see \Automattic\WooCommerce\Admin\API\Reports\Stock\Stats\Query} class from WooCommerce.
 *
 * @see https://woocommerce.github.io/code-reference/classes/Automattic-WooCommerce-Admin-API-Reports-Stock-Stats-Query.html
 */
class StockStatsRepository
{
    protected static ?Query $wooCommerceStockQuery = null;

    /**
     * Gets an array with information about the number of products currently available in the store and their stock status.
     *
     * @return array<string, int>|null
     */
    public static function getProductCountByStockStatus() : ?array
    {
        $query = static::getQueryInstance();

        return $query ? $query->get_data() : null;
    }

    /**
     * Gets an instance of WooCommerce's query class for stock reports.
     *
     * @return Query|null
     */
    public static function getQueryInstance() : ?Query
    {
        if (static::$wooCommerceStockQuery) {
            return static::$wooCommerceStockQuery;
        }

        if (! class_exists(Query::class)) {
            SentryException::getNewInstance("WooCommerce's query class for Stock reports is not available.");

            return null;
        }

        try {
            static::$wooCommerceStockQuery = new Query();
        } catch (Throwable $exception) {
            SentryException::getNewInstance("Could not instantiate WooCommerce's query class for Stock reports.", $exception);

            return null;
        }

        return static::$wooCommerceStockQuery;
    }
}
