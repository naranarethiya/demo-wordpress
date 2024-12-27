<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\CostOfGoods\WC_COG;
use function GoDaddy\WordPress\MWC\CostOfGoods\wc_cog;

/**
 * Disables the "Cost" column for Cost of Goods when the Commerce feature is running.
 */
class DisableProductCostColumnHandler extends AbstractInterceptorHandler
{
    /**
     * Removes the "Cost" column for Cost of Goods in the Products admin screen.
     *
     * @see WC_COG_Admin_Products::init_hooks()
     *
     * @param array<mixed> $args inherited from parent (unused in this context)
     * @return void
     */
    public function run(...$args) : void
    {
        $instance = $this->getCostOfGoodsInstance();

        if (! $instance) {
            return;
        }

        $adminProductsHandler = $instance->get_admin_instance()->get_products_instance();

        remove_filter('manage_edit-product_columns', [$adminProductsHandler, 'product_list_table_cost_column_header'], 11);
        remove_action('manage_product_posts_custom_column', [$adminProductsHandler, 'product_list_table_cost_column'], 11);
        remove_filter('manage_edit-product_sortable_columns', [$adminProductsHandler, 'product_list_table_cost_column_sortable'], 11);
        remove_filter('request', [$adminProductsHandler, 'product_list_table_cost_column_orderby'], 11);
    }

    /**
     * Gets Cost of Goods main instance.
     *
     * @return WC_COG|null
     */
    protected function getCostOfGoodsInstance() : ?WC_COG
    {
        return function_exists('\GoDaddy\WordPress\MWC\CostOfGoods\wc_cog') ? wc_cog() : null;
    }
}
