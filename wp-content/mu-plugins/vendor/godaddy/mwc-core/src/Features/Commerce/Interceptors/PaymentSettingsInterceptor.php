<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;

/**
 * Overrides WooCommerce payment settings.
 */
class PaymentSettingsInterceptor extends AbstractInterceptor
{
    /**
     * Filters the "woocommerce_godaddy-payments-payinperson_settings" option to forcibly disable the push/pull settings.
     *
     * This is disabled when the Commerce feature is turned on, as the Poynt sync is effectively replaced by the
     * Commerce integrations.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::filter()
            ->setGroup('option_woocommerce_godaddy-payments-payinperson_settings')
            ->setHandler([$this, 'overridePayInPersonSettings'])
            ->setArgumentsCount(2)
            ->setPriority(PHP_INT_MAX)
            ->execute();
    }

    /**
     * Overrides the GoDaddy "Pay in Person" payment settings to forcibly disable the sync push and pull options.
     *
     * @internal
     *
     * @param array<string, mixed>|mixed $value
     * @param string|mixed $optionName
     * @return array<string, mixed>|mixed
     */
    public function overridePayInPersonSettings($value, $optionName)
    {
        if (! is_array($value)) {
            return $value;
        }

        $value['sync_push_enabled'] = 'no';
        $value['sync_pull_enabled'] = 'no';

        return $value;
    }
}
