<?php

namespace GoDaddy\WordPress\MWC\Core\Features\SequentialOrderNumbers\Settings;

use Exception;
use GoDaddy\WordPress\MWC\Common\Settings\Models\Control;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\CanGetWooCommerceSettingsDataStoreTrait;
use GoDaddy\WordPress\MWC\Core\Settings\Models\SettingGroup;
use function GoDaddy\WordPress\MWC\SequentialOrderNumbers\wc_seq_order_number_pro;

/**
 * NOTE: at the moment this is only used to broadcast events.
 */
class SequentialOrderNumbersSettings extends SettingGroup
{
    use CanGetWooCommerceSettingsDataStoreTrait;

    /** @var string ID of the settings group */
    const GROUP_ID = 'sequential_order_numbers_pro';

    /** @var string ID of the "Starting number" setting */
    const SETTING_ID_STARTING_NUMBER = 'starting_number';

    /** @var string ID of the "Prefix" setting */
    const SETTING_ID_PREFIX = 'prefix';

    /** @var string ID of the "Suffix" setting */
    const SETTING_ID_SUFFIX = 'suffix';

    /** @var string ID of the "Skip Free Orders" setting */
    const SETTING_ID_SKIP_FREE_ORDERS = 'skip_free_orders';

    /** @var string ID of the "Free Orders Prefix" setting */
    const SETTING_ID_FREE_ORDERS_PREFIX = 'free_orders_prefix';

    /**
     * GeneralSettings constructor.
     */
    public function __construct()
    {
        $this->id = $this->name = self::GROUP_ID;

        $this->label = __('Order Numbers', 'mwc-core');
    }

    /**
     * Gets the initial settings.
     *
     * @return SequentialOrderNumbersSetting[]
     * @throws Exception
     */
    protected function getInitialSettings() : array
    {
        $plugin = wc_seq_order_number_pro();

        return [
            (new SequentialOrderNumbersSetting())
                ->setId(static::SETTING_ID_STARTING_NUMBER)
                ->setName(static::SETTING_ID_STARTING_NUMBER)
                ->setLabel(__('Starting number', 'mwc-core'))
                ->setType(SequentialOrderNumbersSetting::TYPE_STRING)
                ->setValue((string) $plugin->get_order_number_start())
                ->setControl((new Control())
                    ->setType(Control::TYPE_TEXT)
                ),

            (new SequentialOrderNumbersSetting())
                ->setId(static::SETTING_ID_PREFIX)
                ->setName(static::SETTING_ID_PREFIX)
                ->setLabel(__('Prefix', 'mwc-core'))
                ->setType(SequentialOrderNumbersSetting::TYPE_STRING)
                ->setValue($plugin->get_order_number_prefix())
                ->setControl((new Control())
                    ->setType(Control::TYPE_TEXT)
                ),

            (new SequentialOrderNumbersSetting())
                ->setId(static::SETTING_ID_SUFFIX)
                ->setName(static::SETTING_ID_SUFFIX)
                ->setLabel(__('Suffix', 'mwc-core'))
                ->setType(SequentialOrderNumbersSetting::TYPE_STRING)
                ->setValue($plugin->get_order_number_suffix())
                ->setControl((new Control())
                    ->setType(Control::TYPE_TEXT)
                ),

            (new SequentialOrderNumbersSetting())
                ->setId(static::SETTING_ID_SKIP_FREE_ORDERS)
                ->setName(static::SETTING_ID_SKIP_FREE_ORDERS)
                ->setLabel(__('Skip free orders', 'mwc-core'))
                ->setType(SequentialOrderNumbersSetting::TYPE_BOOLEAN)
                ->setValue($plugin->skip_free_orders())
                ->setControl((new Control())
                    ->setType(Control::TYPE_CHECKBOX)
                ),

            (new SequentialOrderNumbersSetting())
                ->setId(static::SETTING_ID_FREE_ORDERS_PREFIX)
                ->setName(static::SETTING_ID_FREE_ORDERS_PREFIX)
                ->setLabel(__('Free order identifier', 'mwc-core'))
                ->setType(SequentialOrderNumbersSetting::TYPE_STRING)
                ->setValue($plugin->get_free_order_number_prefix())
                ->setControl((new Control())
                    ->setType(Control::TYPE_TEXT)
                ),
        ];
    }
}
