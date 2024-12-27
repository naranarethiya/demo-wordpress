<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations;

use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\AbstractIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Interceptors\LocalPickupAdminInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Interceptors\LocalPickupCustomerInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Interceptors\LocalPickupEmailsInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\IntegrationEnabledOnTestTrait;
use WC_Shipping_Zones;

class LocationsIntegration extends AbstractIntegration
{
    use IntegrationEnabledOnTestTrait;

    public const NAME = 'locations';

    /** @var class-string<ComponentContract>[] */
    protected array $componentClasses = [
        LocalPickupAdminInterceptor::class,
        LocalPickupCustomerInterceptor::class,
        LocalPickupEmailsInterceptor::class,
    ];

    /**
     * {@inheritDoc}
     */
    protected static function getIntegrationName() : string
    {
        return self::NAME;
    }

    /**
     * Determines whether the store has at least one pickup location added.
     *
     * This method gets all shipping zones to iterate over their shipping methods. If the shipping method is of local
     * pickup type, it tries to get its GoDaddy Commerce Locations set to check if that list has at least one.
     *
     * @return bool
     */
    public static function hasPickupLocationAdded() : bool
    {
        $shippingZones = ArrayHelper::wrap(WC_Shipping_Zones::get_zones());

        foreach ($shippingZones as $zone) {
            foreach (ArrayHelper::wrap(ArrayHelper::get($zone, 'shipping_methods', [])) as $shippingMethod) {
                $isLocalPickup = 'local_pickup' === ($shippingMethod->id ?? null);
                $isEnabled = 'yes' === ($shippingMethod->enabled ?? 'no');
                $instanceSettings = ($shippingMethod->instance_settings ?? null);

                if ($isLocalPickup && $isEnabled && $instanceSettings) {
                    $goDaddyCommerceLocations = array_filter(ArrayHelper::wrap(ArrayHelper::get(ArrayHelper::wrap($instanceSettings), 'godaddy_commerce_locations', [])));

                    if (! empty($goDaddyCommerceLocations)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
