<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Traits\ShouldLoadOnlyIfWooCommerceIsEnabledTrait;

/**
 * A WooCommerce interceptor that alters the WC Logger retention period (in days).
 */
class WcLogRetentionInterceptor extends AbstractInterceptor
{
    use ShouldLoadOnlyIfWooCommerceIsEnabledTrait;

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::filter()
            ->setGroup('woocommerce_logger_days_to_retain_logs')
            ->setHandler([$this, 'getWooCommerceLogRetentionDays'])
            ->execute();
    }

    /**
     * Returns the number of days to retain WooCommerce logs.
     *
     * @return int
     */
    public function getWooCommerceLogRetentionDays() : int
    {
        $default = TypeHelper::int(Configuration::get('reporting.logging.woocommerce.retentionDays.default'), 7);

        return TypeHelper::int(Configuration::get('reporting.logging.woocommerce.retentionDays.override'), $default);
    }
}
