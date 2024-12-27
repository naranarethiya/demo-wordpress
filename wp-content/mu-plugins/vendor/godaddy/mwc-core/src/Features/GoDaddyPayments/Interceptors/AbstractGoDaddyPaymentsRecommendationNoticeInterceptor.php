<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Interceptors;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use WC_Shipping_Zones;

abstract class AbstractGoDaddyPaymentsRecommendationNoticeInterceptor extends AbstractGoDaddyPaymentsNoticeInterceptor
{
    /** @var string WC Local Pickup Shipping Method id */
    public const WC_LOCAL_PICKUP = 'local_pickup';

    /** @var string Local Delivery SHipping Method id */
    public const MWC_LOCAL_DELIVERY = 'mwc_local_delivery';

    /** @var string[] sections in which to display GoDaddy Payment Recommendation */
    public const RECOMMENDATION_SECTIONS = ['local_pickup_plus', 'cod'];

    /** @var string[] tabs in which to display GoDaddy Payment Recommendation */
    public const RECOMMENDATION_TABS = ['shipping'];

    public static function shouldLoad() : bool
    {
        if (! self::isBOPITFeatureEnabled() || ! self::isOnSettingsPage()) {
            return false;
        }

        if (static::isOnRecommendationSection()) {
            return true;
        }

        // only continue loading if on the correct tab AND at least one of local pickup or local delivery is enabled
        if (! static::isOnRecommendationTab() || (! static::isLocalPickupEnabled() && ! static::isLocalDeliveryEnabled())) {
            return false;
        }

        return parent::shouldLoad();
    }

    /**
     * Determines whether the BOPIT feature is active.
     *
     * @return bool
     */
    public static function isBOPITFeatureEnabled() : bool
    {
        return TypeHelper::bool(Configuration::get('features.bopit'), false);
    }

    /**
     * Determines whether we're on the WC settings page.
     *
     * @return bool
     */
    protected static function isOnSettingsPage() : bool
    {
        return 'wc-settings' === ArrayHelper::get($_GET, 'page');
    }

    /**
     * Determines whether we're on one of the sections that should show recommendation notices.
     *
     * @return bool
     */
    public static function isOnRecommendationSection() : bool
    {
        return ArrayHelper::contains(static::RECOMMENDATION_SECTIONS, ArrayHelper::get($_GET, 'section'));
    }

    /**
     * Determines whether we're on one of the tabs that should show recommendation notices.
     *
     * @return bool
     */
    public static function isOnRecommendationTab() : bool
    {
        return ArrayHelper::contains(static::RECOMMENDATION_TABS, ArrayHelper::get($_GET, 'tab'));
    }

    /**
     * Determines whether the shipping zones have Local Pickup Method enabled.
     *
     * @return bool
     */
    protected static function isLocalPickupEnabled() : bool
    {
        return static::isShippingMethodEnabled(static::WC_LOCAL_PICKUP);
    }

    /**
     * Determines whether the shipping zones have Local Delivery Method enabled.
     *
     * @return bool
     */
    protected static function isLocalDeliveryEnabled() : bool
    {
        return static::isShippingMethodEnabled(static::MWC_LOCAL_DELIVERY);
    }

    /**
     * Determines whether the shipping zones have the given shipping method enabled.
     *
     * @param string $shippingMethodId
     * @return bool
     */
    protected static function isShippingMethodEnabled(string $shippingMethodId) : bool
    {
        $shippingZones = WooCommerceRepository::isWooCommerceActive() ? WC_Shipping_Zones::get_zones() : [];

        foreach (ArrayHelper::wrap($shippingZones) as $zone) {
            if (! empty(ArrayHelper::where(TypeHelper::array(ArrayHelper::get($zone, 'shipping_methods'), []), static function ($method) use ($shippingMethodId) {
                return $shippingMethodId === $method->id;
            }))) {
                return true;
            }
        }

        return false;
    }
}
