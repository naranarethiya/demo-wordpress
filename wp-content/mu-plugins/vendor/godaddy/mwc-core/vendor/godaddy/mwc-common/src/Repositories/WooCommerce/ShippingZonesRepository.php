<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce;

use WC_Shipping_Zone;
use WC_Shipping_Zones;

class ShippingZonesRepository
{
    /**
     * Gets an array of the available shipping zones.
     *
     * @return WC_Shipping_Zone[]
     */
    public static function getShippingZones() : array
    {
        $zones = [];

        foreach (array_keys(WC_Shipping_Zones::get_zones()) as $zoneId) {
            if (! $zone = static::getShippingZone($zoneId)) {
                continue;
            }

            $zones[] = $zone;
        }

        return $zones;
    }

    /**
     * Gets a {@see WC_Shipping_Zone} instance for the shipping zone with the given ID.
     *
     * @param int $shippingZoneId
     * @return WC_Shipping_Zone|null
     */
    public static function getShippingZone(int $shippingZoneId) : ?WC_Shipping_Zone
    {
        $zone = WC_Shipping_Zones::get_zone($shippingZoneId);

        return $zone instanceof WC_Shipping_Zone ? $zone : null;
    }
}
