<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers\DisableProductCostColumnHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Traits\CanLoadWhenReadsEnabledTrait;
use GoDaddy\WordPress\MWC\Core\Features\CostOfGoods\CostOfGoods;
use GoDaddy\WordPress\MWC\CostOfGoods\Admin\WC_COG_Admin_Products;

/**
 * Handles Cost of Goods feature related hooks.
 *
 * @see CostOfGoods
 */
class CostOfGoodsInterceptor extends AbstractInterceptor
{
    use CanLoadWhenReadsEnabledTrait;

    /**
     * Removes the Cost of Goods hooks that are not compatible with the Commerce feature.
     *
     * @see WC_COG_Admin_Products::init_hooks()
     *
     * @return void
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup('admin_init')
            ->setPriority(PHP_INT_MAX)
            ->setHandler([DisableProductCostColumnHandler::class, 'handle'])
            ->execute();
    }
}
