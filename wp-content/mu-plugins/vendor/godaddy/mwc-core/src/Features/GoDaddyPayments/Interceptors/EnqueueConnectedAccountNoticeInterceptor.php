<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notices;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices\ConnectedAccountNotice;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices\ConnectedAccountSwitchedNotice;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Interceptors\AutoConnectInterceptor;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Models\Business;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;

class EnqueueConnectedAccountNoticeInterceptor extends AbstractGoDaddyPaymentsNoticeInterceptor
{
    /**
     * Determines whether the component should be loaded or not.
     *
     * @throws Exception
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        if (true !== Configuration::get('features.gdp_by_default.enabled')) {
            return false;
        }

        if (! static::shouldLoadForCurrentPage()) {
            return false;
        }

        if (! static::getConnectedBusiness()) {
            return false;
        }

        return parent::shouldLoad();
    }

    /**
     * Determines if the GDP connection notices should load for the current page.
     *
     * This is currently limited to:
     * - WooCommerce -> Settings -> General
     * - WooCommerce -> Settings -> Payments
     * - WooCommerce -> Settings -> Payments -> GoDaddy Payments
     *
     * @return bool
     * @throws Exception
     */
    protected static function shouldLoadForCurrentPage() : bool
    {
        if ('wc-settings' !== ArrayHelper::get($_GET, 'page')) {
            return false;
        }

        $tab = ArrayHelper::get($_GET, 'tab');

        if ($tab && ! ArrayHelper::contains(['general', 'checkout'], $tab)) {
            return false;
        }

        $section = ArrayHelper::get($_GET, 'section');

        if ($section && 'poynt' !== $section) {
            return false;
        }

        return Poynt::isConnected() && AutoConnectInterceptor::wasConnected();
    }

    /**
     * Gets the connected business.
     *
     * @return Business|null
     */
    protected static function getConnectedBusiness() : ?Business
    {
        try {
            return Poynt::getBusiness();
        } catch (Exception $exception) {
            return null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function enqueueNotice() : void
    {
        Notices::enqueueAdminNotice(Onboarding::hasSwitchedAccounts()
            ? ConnectedAccountSwitchedNotice::getNewInstance()
            : ConnectedAccountNotice::getNewInstance()
        );
    }
}
