<?php

namespace GoDaddy\WordPress\MWC\Core\Features\ShipmentTracking;

use GoDaddy\WordPress\MWC\Common\Features\AbstractFeature;
use GoDaddy\WordPress\MWC\Core\Features\Shipping\Shipping;

/**
 * The ShipmentTracking feature loader.
 */
class ShipmentTracking extends AbstractFeature
{
    /**
     * Gets the feature name, matching the key used in configuration.
     *
     * @return string
     */
    public static function getName() : string
    {
        return 'shipment_tracking';
    }

    /**
     * Initializes this feature.
     */
    public function load() : void
    {
    }

    /**
     * Determines whether feature should be visible in the features section in the admin.
     *
     * @return bool
     */
    public static function shouldBeVisible() : bool
    {
        return static::shouldLoad() && ! Shipping::shouldBeVisible();
    }
}
