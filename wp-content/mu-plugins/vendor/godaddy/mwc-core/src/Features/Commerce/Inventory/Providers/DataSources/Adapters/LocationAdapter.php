<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Helpers\AddressHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Location;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Address;

class LocationAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /**
     * This method is no-op.
     */
    public function convertFromSource() : void
    {
        // No-op
    }

    /**
     * Returns a location data object populated by the current site data.
     *
     * @return Location
     */
    public function convertToSource() : Location
    {
        $shopAddress = WooCommerceRepository::getShopAddress();

        $addressDataObject = new Address([
            'address1'   => TypeHelper::string(ArrayHelper::get($shopAddress->getLines(), '0', ''), ''),
            'address2'   => TypeHelper::string(ArrayHelper::get($shopAddress->getLines(), '1', ''), ''),
            'city'       => TypeHelper::string($shopAddress->getLocality(), ''),
            'state'      => TypeHelper::string(ArrayHelper::get($shopAddress->getAdministrativeDistricts(), '0', ''), ''),
            'postalCode' => TypeHelper::string($shopAddress->getPostalCode(), ''),
            'country'    => TypeHelper::string($shopAddress->getCountryCode(), ''),
        ]);

        return new Location([
            'active'   => true,
            'address'  => AddressHelper::sanitizeAddress($addressDataObject),
            'priority' => 0,
            'type'     => 'WAREHOUSE',
        ]);
    }
}
