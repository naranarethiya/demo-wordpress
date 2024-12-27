<?php

namespace GoDaddy\WordPress\MWC\Common\Content;

use Automattic\WooCommerce\Internal\Admin\Orders\ListTable;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use WC_Data;

/**
 * Object representation of an orders table column as used in HPOS via {@see ListTable}.
 */
abstract class AbstractWooCommerceOrdersTableColumn extends AbstractPostsTableColumn
{
    /** @var string actually not a post type any longer but the related object type with HPOS */
    protected $postType = 'shop_order';

    /**
     * Gets the register column filter hook name.
     *
     * @return string
     */
    protected function getRegisterColumnFilterHook() : string
    {
        return WooCommerceRepository::isCustomOrdersTableUsageEnabled()
            ? 'woocommerce_shop_order_list_table_columns'
            : parent::getRegisterColumnFilterHook();
    }

    /**
     * Gets the render column action hook name.
     *
     * @return string
     */
    protected function getRenderColumnActionHook() : string
    {
        return WooCommerceRepository::isCustomOrdersTableUsageEnabled()
            ? 'manage_woocommerce_page_wc-orders_custom_column'
            : parent::getRenderColumnActionHook();
    }

    /**
     * Gets the column identifier for the corresponding order ID or object.
     *
     * @param int|WC_Data $idOrObject
     * @return int
     */
    protected function getIdentifier($idOrObject) : int
    {
        return $idOrObject instanceof WC_Data
            ? $idOrObject->get_id()
            : parent::getIdentifier($idOrObject);
    }
}
