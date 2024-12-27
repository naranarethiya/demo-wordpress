<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;

/**
 * Intercepts the "Manage Stock?" global setting in Settings > Products > Inventory to ensure it's always enabled.
 * This can still be modified on a per-product basis.
 *
 * Will also handle Inventory settings descriptions.
 */
class StockManagementSettingInterceptor extends AbstractInterceptor
{
    /**
     * Ensures "Manage stock?" is always enabled globally and cannot be disabled.
     *
     * Also updates "Manage stock?" and "Stock display format" settings descriptions.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::filter()
            ->setGroup('pre_option_woocommerce_manage_stock')
            ->setHandler([$this, 'enableManageStock'])
            ->setPriority(PHP_INT_MAX)
            ->execute();

        Register::filter()
            ->setGroup('woocommerce_inventory_settings')
            ->setHandler([$this, 'handleProductInventorySettings'])
            ->execute();
    }

    /**
     * Returns `'yes'` to indicate the setting is enabled.
     *
     * @internal
     *
     * @return string
     */
    public function enableManageStock() : string
    {
        return 'yes';
    }

    /**
     * Adds the `disabled` property to the "Manage stock?" setting so that the checkbox cannot be unchecked.
     *
     * Also updates:
     *  - The description of "Manage stock?" to indicate why it's disabled.
     *  - The description of "Stock display format" to mention that in frontend the reserved stock is not accounted for.
     *
     * @internal
     *
     * @param array<string, mixed>|mixed $settings
     * @return array<string, mixed>|mixed
     */
    public function handleProductInventorySettings($settings)
    {
        if (! ArrayHelper::accessible($settings)) {
            return $settings;
        }

        foreach ($settings as $key => $setting) {
            switch (ArrayHelper::get($setting, 'id')) {
                case 'woocommerce_manage_stock':
                    if (! isset($setting['desc'])) {
                        $settings[$key]['desc'] = '';
                    }

                    $settings[$key]['disabled'] = true;
                    $settings[$key]['desc'] .= sprintf(
                        /* translators: %1$s opening anchor tag; %2$s closing anchor tag */
                        '<p class="description">'.__('Required for storing Product data in Commerce Home. Stock management can be enabled or disabled per product. Learn more about %1$sproduct inventory settings%2$s.', 'mwc-core').'</p>',
                        '<a href="https://woocommerce.com/document/managing-products/#product-data" target="_blank">',
                        '</a>',
                    );
                    break;
                case 'woocommerce_stock_format':
                    if (! empty($setting['desc'])) {
                        $settings[$key]['desc_tip'] = $setting['desc'];
                    }

                    $settings[$key]['desc'] = '<p class="description">'.__('Inventory amounts displayed on the frontend reflect total available for purchase and exclude reserved stock.', 'mwc-core').'</p>';
                    break;
            }
        }

        return $settings;
    }
}
