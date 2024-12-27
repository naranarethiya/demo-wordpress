<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notices;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices\PoyntAlreadyIncludedNotice;

class EnqueuePoyntPluginNoticeInterceptor extends AbstractGoDaddyPaymentsNoticeInterceptor
{
    /** @var string path for the GoDaddy Payments plugin */
    public const GODADDY_PAYMENTS_PLUGIN_PATH = 'godaddy-payments/godaddy-payments.php';

    /**
     * Determines whether the component should be loaded or not.
     *
     * @throws Exception
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        if (function_exists('is_plugin_active') && ! is_plugin_active(static::GODADDY_PAYMENTS_PLUGIN_PATH)) {
            return false;
        }

        return parent::shouldLoad();
    }

    /**
     * {@inheritDoc}
     */
    public function enqueueNotice() : void
    {
        Notices::enqueueAdminNotice(PoyntAlreadyIncludedNotice::getNewInstance());
    }
}
