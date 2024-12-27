<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Repositories;

use Exception;
use GoDaddy\WordPress\MWC\Common\Cache\Cache;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\OrderAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Repositories\OrdersRepository;

/**
 * Repository class for handling marketplaces orders.
 */
class OrderRepository
{
    /** @var string meta key for metadata storing a counter of Marketplaces orders */
    protected const GDM_COUNT_ORDERS_BY_META_KEY = 'gdm_count_orders_by_meta_key';

    /**
     * Gets the count of the Marketplaces orders for the current month.
     *
     * @return int
     */
    protected static function getCurrentMonthMarketplacesOrdersCount() : int
    {
        $cache = (new Cache())->key('gdm_monthly_order_count')->expires(86400);
        $count = $cache->get();

        if (is_numeric($count)) {
            return (int) $count;
        }

        try {
            static::maybeExecuteOrdersQueryCustomArgumentFilter();
        } catch (Exception $exception) {
            return 0;
        }

        $count = count(OrdersRepository::query(static::getOrdersQueryArguments()));

        $cache->set($count);

        return $count;
    }

    /**
     * Gets an interval for the current month formatted for {@see wc_get_orders()} query.
     *
     * @return string
     */
    protected static function getCurrentMonthQueryInterval() : string
    {
        return strtotime(date('Y-m-1 00:00:00')).'...'.strtotime(date('Y-m-t 23:59:59'));
    }

    /**
     * Gets the {@see WC_Order_Query} arguments necessary to count orders that have a Marketplaces channel UUID.
     *
     * @return array<string, mixed>
     */
    protected static function getOrdersQueryArguments() : array
    {
        $args = [
            'limit'        => -1,
            'return'       => 'ids',
            'date_created' => static::getCurrentMonthQueryInterval(),
        ];

        if (WooCommerceRepository::isCustomOrdersTableUsageEnabled()) {
            return static::setOrdersQueryMetaQuery($args, OrderAdapter::MARKETPLACES_CHANNEL_UUID_META_KEY);
        }

        $args[static::GDM_COUNT_ORDERS_BY_META_KEY] = OrderAdapter::MARKETPLACES_CHANNEL_UUID_META_KEY;

        return $args;
    }

    /**
     * Registers a handler for the `woocommerce_order_data_store_cpt_get_orders_query` filter if the HPOS feature is disabled.
     *
     * @return void
     * @throws Exception
     */
    protected static function maybeExecuteOrdersQueryCustomArgumentFilter() : void
    {
        if (WooCommerceRepository::isCustomOrdersTableUsageEnabled()) {
            return;
        }

        Register::filter()
            ->setGroup('woocommerce_order_data_store_cpt_get_orders_query')
            ->setHandler([__CLASS__, 'handleCountOrdersByMetaKeyQuery'])
            ->setArgumentsCount(2)
            ->execute();
    }

    /**
     * Handler for the `woocommerce_order_data_store_cpt_get_orders_query` filter.
     *
     * @param mixed $query
     * @param mixed $args
     * @return mixed
     */
    public static function handleCountOrdersByMetaKeyQuery($query, $args)
    {
        if (! ArrayHelper::accessible($query) || ! ArrayHelper::accessible($args)) {
            return $query;
        }

        if ($metaKey = TypeHelper::string(ArrayHelper::get($args, static::GDM_COUNT_ORDERS_BY_META_KEY), '')) {
            $query = static::setOrdersQueryMetaQuery($query, $metaKey);
        }

        return $query;
    }

    /**
     * Updates the given query with meta_query parameters to check that the given meta key exists.
     *
     * @param array<string, mixed> $query
     * @param non-empty-string $metaKey
     * @return array<string, mixed>
     */
    protected static function setOrdersQueryMetaQuery(array $query, string $metaKey) : array
    {
        // account for existing meta query key in the query arguments
        if (! ArrayHelper::exists($query, 'meta_query') || ! ArrayHelper::accessible($query['meta_query'])) {
            $query['meta_query'] = [];
        } elseif (! ArrayHelper::exists($query['meta_query'], 'relation')) {
            $query['meta_query']['relation'] = 'AND';
        }

        $query['meta_query'][] = [
            'key'     => $metaKey,
            'compare' => 'EXISTS',
        ];

        return $query;
    }

    /**
     * Gets the quota of monthly Marketplaces orders according to merchant's plan.
     *
     * In case the plan has no associated quota, this will return the highest possible number.
     *
     * @return int
     */
    protected static function getMonthlyMarketplacesOrdersPlanLimit() : int
    {
        try {
            $planName = PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getPlan()->getName();
        } catch (Exception $exception) {
            return PHP_INT_MAX;
        }

        $planLimits = Configuration::get('marketplaces.plan_limits', []);

        return (int) ArrayHelper::get($planLimits, $planName, PHP_INT_MAX);
    }

    /**
     * Determines if the site has reached the monthly quota of Marketplaces orders.
     *
     * @return bool
     */
    public static function hasReachedMonthlyMarketplacesOrdersLimit() : bool
    {
        return static::getCurrentMonthMarketplacesOrdersCount() >= static::getMonthlyMarketplacesOrdersPlanLimit();
    }

    /**
     * Determines if the site has nearly reached (90%) the monthly quota of Marketplaces orders.
     *
     * @return bool
     */
    public static function isNearMonthlyMarketplacesOrdersLimit() : bool
    {
        return ! static::hasReachedMonthlyMarketplacesOrdersLimit()
            && static::getCurrentMonthMarketplacesOrdersCount() >= static::getMonthlyMarketplacesOrdersPlanLimit() * 0.9;
    }
}
